var oauthapi = {
    authorize: function(options) {
        var deferred = $.Deferred();

        //Build the OAuth consent page URL
        var authUrl = 'https://www.linkedin.com/uas/oauth2/authorization?' + $.param({
            client_id: options.client_id,
            redirect_uri: options.redirect_uri,
            response_type: 'code',
            scope: options.scope,
            state: 'estoyprobandolinkedin'
        });

        //Open the OAuth consent page in the InAppBrowser
        var authWindow = window.open(authUrl, '_blank', 'location=no,toolbar=no');

        //The recommendation is to use the redirect_uri "urn:ietf:wg:oauth:2.0:oob" (for google)
        //which sets the authorization code in the browser's title. However, we can't
        //access the title of the InAppBrowser.
        //
        //Instead, we pass a bogus redirect_uri of "http://localhost", which means the
        //authorization code will get set in the url. We can access the url in the
        //loadstart and loadstop events. So if we bind the loadstart event, we can
        //find the authorization code and close the InAppBrowser after the user
        //has granted us access to their data.
        $(authWindow).on('loadstart', function(e) {
            var url = e.originalEvent.url;
            var code = /\?code=(.+)$/.exec(url);
            var error = /\?error=(.+)$/.exec(url);

            if (code || error) {
                //Always close the browser when match is found
                authWindow.close();
            }

            if (code) {
                //Exchange the authorization code for an access token
                var a_data = code[1].split('&');
                final_code = a_data[0];

                $.post('https://www.linkedin.com/uas/oauth2/accessToken', {
                    //code: code[1],
                    code: final_code,
                    client_id: options.client_id,
                    client_secret: options.client_secret,
                    redirect_uri: options.redirect_uri,
                    grant_type: 'authorization_code'
                }).done(function(data) {
                    deferred.resolve(data);
                }).fail(function(response) {
                    deferred.reject(response.responseJSON);
                });
            } else if (error) {
                //The user denied access to the app
                deferred.reject({
                    error: error[1]
                });
            }
        });

        return deferred.promise();
    }
};

$(document).on('deviceready', function() {
    var $loginButton = $('#login a');
    var $loginStatus = $('#login p');

    $loginButton.on('click', function() {
        $loginStatus.html('Redirecting...');
        oauthapi.authorize({
            client_id: 'c9wugich9zjr',
            client_secret: 'CZBPanRvmvBzzhR4',
            redirect_uri: 'http://localhost',
            scope: 'r_basicprofile r_emailaddress'
        }).done(function(data) {
            var url = 'https://api.linkedin.com/v1/people/~:(id,first-name,last-name,email-address,picture-url,site-standard-profile-request)?oauth2_access_token='+data.access_token;
            $loginStatus.html('This may take a few moments.');
            hereWeGo(url);
        }).fail(function(data) {
            $loginStatus.html(data.error+': '+data.error_description+'<br/><br/>');
        });
    });
});



function hereWeGo(url){
    connection = crearXMLHttpRequest();
    connection.onreadystatechange = responseAjax;
    connection.open('POST',url_api_base+'linkedin/3', true);
    connection.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    connection.send(JSON.stringify({"url":url}));
  }
  


function responseAjax(){
     if(connection.readyState == 4){
        if(connection.status == 200){
            var result = connection.responseText;
            //alert(result);
            var obj = JSON.parse(result);
            
            localStorage.setItem("logged_user", obj["user_id"]);
            
            var logged_user = localStorage.getItem('logged_user') || '<empty>';
            
            if(obj["type"] == "signup"){
                window.location = "signup2.html?id="+obj["user_id"];
            }else{
                localStorage.setItem("speaker", obj["speaker"]);
                window.location = "home.html";
            }
        }else{
            alert("An error has occurred: "+connection.statusText);
        }
     }
  }

