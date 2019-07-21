
/*
 * This function will try to force browser to request
 * login again, but it's not really confiable, so avoid
 * to depend entirely on it.
 */
function logout() {

  var baseURL = window.location.origin+window.location.pathname;

  var xmlhttp;
  if (window.XMLHttpRequest) {
    xmlhttp = new XMLHttpRequest();
  }
  // code for IE
  else if (window.ActiveXObject) {
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  if (window.ActiveXObject) {
    // IE clear HTTP Authentication
    document.execCommand("ClearAuthenticationCache");
    window.location.href=baseURL;
  } else {
    xmlhttp.open("GET", baseURL+'assets/logout.js', true, "logout", "logout");
    xmlhttp.send("");
    xmlhttp.onreadystatechange = function() {
      if (xmlhttp.readyState == 4) {window.location.href=baseURL;}
    }
  }
  return false;
}
