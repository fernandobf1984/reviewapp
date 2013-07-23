//Common functions and data for app

var url_api_base = 'http://192.168.1.141/api/index.php/';


function crearXMLHttpRequest() 
{
  var xmlHttp = null;
  if(window.ActiveXObject){ 
    xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
  }else{ 
    if (window.XMLHttpRequest){ 
      xmlHttp = new XMLHttpRequest();
    }
  }
  return xmlHttp;
}


function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}


	function countrySelected(){
		var id_country = document.getElementById('select_countries').value;
		if(id_country != '-'){
			getRegions(id_country);
		} 
	  }
	  
	function regionSelected(){
		var id_region = document.getElementById('select_regions').value;
		if(id_region != '-'){
			getCities(id_region);
		} 
	  }
	  
	function getCountries(){
	    connection = crearXMLHttpRequest();
  		connection.onreadystatechange = responseAjaxCountries;
  		connection.open('GET',url_api_base+'countries', true);
  		connection.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
        connection.send();
	}
	
	function getRegions(id_country){
	    connection = crearXMLHttpRequest();
  		connection.onreadystatechange = responseAjaxRegions;
  		connection.open('GET',url_api_base+'regions/'+id_country, true);
  		connection.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
        connection.send();
	}
	
	function getCities(id_region){
	    connection = crearXMLHttpRequest();
  		connection.onreadystatechange = responseAjaxCities;
  		connection.open('GET',url_api_base+'cities/'+id_region, true);
  		connection.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
        connection.send();
	}
	
	function responseAjaxCountries(){
		if(connection.readyState == 4){
	        if(connection.status == 200){
	            result = connection.responseText;
	            var obj = JSON.parse(result);
	            var sel = document.getElementById("select_countries");
	            
	            for(var i = 1; i < obj["countries"].length; i++){
					option = document.createElement("OPTION");
					option.value = obj["countries"][i]['id'];
					option.text = obj["countries"][i]['local_name'];
					sel.add(option);
				}
	        }else{
	            alert("An error has occurred: "+connection.statusText);
	        }
	     }
	}
	
	function responseAjaxRegions(){
		var select_cities = document.getElementById("select_cities");
		initSelect(select_cities);
		var sel = document.getElementById("select_regions");
		initSelect(sel);
		if(connection.readyState == 4){
	        if(connection.status == 200){
	            result = connection.responseText;
	            var obj = JSON.parse(result);
	            var sel = document.getElementById("select_regions");
	            
	            for(var i = 1; i < obj["regions"].length; i++){
					option = document.createElement("OPTION");
					option.value = obj["regions"][i]['id'];
					option.text = obj["regions"][i]['local_name'];
					sel.add(option);
				}
	        }else{
	            alert("An error has occurred: "+connection.statusText);
	        }
	     }
	}
	
	function responseAjaxCities(){
		var sel = document.getElementById("select_cities");
		initSelect(sel);
		if(connection.readyState == 4){
	        if(connection.status == 200){
	            result = connection.responseText;
	            var obj = JSON.parse(result);
	            
	            for(var i = 1; i < obj["cities"].length; i++){
					option = document.createElement("OPTION");
					option.value = obj["cities"][i]['id'];
					option.text = obj["cities"][i]['local_name'];
					sel.add(option);
				}
	        }else{
	            alert("An error has occurred: "+connection.statusText);
	        }
	     }
	}
	
	
	function initSelect(select){
		select.options.length = 0;
		option = document.createElement("OPTION");
		option.value = "-";
		option.text = "-";
		select.add(option);
	}
	
	
	function locationHelper(id_country,id_region,id_city){
		//alert(id_country+' - '+id_region+' - '+id_city);
		document.getElementById("CO_aux").value = id_country;
		document.getElementById("RE_aux").value = id_region;
		document.getElementById("CI_aux").value = id_city;
				
		connection = crearXMLHttpRequest();
  		connection.onreadystatechange = responseAjaxCountriesConf;
  		connection.open('GET',url_api_base+'countries', true);
  		connection.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
        connection.send();
		
	}
	
	function responseAjaxCountriesConf(){
		if(connection.readyState == 4){
	        if(connection.status == 200){
	            result = connection.responseText;
	            var obj = JSON.parse(result);
	            var sel = document.getElementById("select_countries");
	            for(var i = 1; i < obj["countries"].length; i++){
					option = document.createElement("OPTION");
					option.value = obj["countries"][i]['id'];
					option.id = obj["countries"][i]['id'];
					option.text = obj["countries"][i]['local_name'];
					sel.add(option);
				}
				var id_country = document.getElementById("CO_aux").value;
				document.getElementById(id_country).selected = 1;
				
				connection = crearXMLHttpRequest();
				connection.onreadystatechange = responseAjaxRegionsConf;
				connection.open('GET',url_api_base+'regions/'+id_country, true);
				connection.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
				connection.send();
	        }else{
	            alert("An error has occurred: "+connection.statusText);
	        }
	     }
	}
	
	function responseAjaxRegionsConf(){
		var sel = document.getElementById("select_regions");
		initSelect(sel);
		if(connection.readyState == 4){
	        if(connection.status == 200){
	            result = connection.responseText;
	            var obj = JSON.parse(result);
	            var sel = document.getElementById("select_regions");
	            
	            for(var i = 1; i < obj["regions"].length; i++){
					option = document.createElement("OPTION");
					option.value = obj["regions"][i]['id'];
					option.id = obj["regions"][i]['id'];
					option.text = obj["regions"][i]['local_name'];
					sel.add(option);
				}
				var id_region = document.getElementById("RE_aux").value;
				
				document.getElementById(id_region).selected = 1;
				connection = crearXMLHttpRequest();
				connection.onreadystatechange = responseAjaxCitiesConf;
				connection.open('GET',url_api_base+'cities/'+id_region, true);
				connection.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
				connection.send();
	        }else{
	            alert("An error has occurred: "+connection.statusText);
	        }
	     }
	}
	
	function responseAjaxCitiesConf(){
		var sel = document.getElementById("select_cities");
		initSelect(sel);
		if(connection.readyState == 4){
	        if(connection.status == 200){
	            result = connection.responseText;
	            var obj = JSON.parse(result);
	            
	            for(var i = 1; i < obj["cities"].length; i++){
					option = document.createElement("OPTION");
					option.value = obj["cities"][i]['id'];
					option.id = obj["cities"][i]['id'];
					option.text = obj["cities"][i]['local_name'];
					sel.add(option);
				}
				var id_city = document.getElementById("CI_aux").value;
				document.getElementById(id_city).selected = 1;
	        }else{
	            alert("An error has occurred: "+connection.statusText);
	        }
	     }
	}
	
	
	
	
    
   
