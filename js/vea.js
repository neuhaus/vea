var domain;

$(document).ready(function() {

  updatedomains();

  // Domains
  $("#newdomainbutton").live("click", function() {
    $("#newdomainbuttonli").hide();
    $("#newdomainli").show();
  });
  $("#newdomainsave").live("click", function() {
    $.getJSON("service.php", { action: "newdomain", domain: $("#newdomaintext").attr("value") }, function(json){
      if (json.error) {
        alert(json.error);
        return false;
      }
      updatedomains();
    });
  });
  $("#newdomaincancel").live("click", function() {
    $("#newdomainbuttonli").show();
    $("#newdomainli").hide();
  });
  $("#newdomaintext").live("focus", function() {
    var value = $(this).attr("value");
    if (value == "newdomain.com") {
      $(this).attr("value", "");
    }
  });
  $(".domainbutton").live("click", function() {
    domain = $(this).text();
    updatedomain();
  });


  $("#deletedomainbutton").live("click", function() {
    if (confirm("do you really want to delete the domain " + domain + "?")) {
      $.getJSON("service.php", { action: "deletedomain", domain: $(this).attr("name") }, function(json){
        if (json.error) {
          alert(json.error);
          return false;
        }
        updatedomains();
        $("#content").html("");
      });
    }
  });

  // Users
  $(".userbutton").live("click", function() {
    $(this).next(".edituser").toggle();
    return (false);
  });
  $("#newuserbutton").live("click", function() {
    $("#newuserbutton").hide();
    $("#newuser").show();
  });
  $("#newusersave").live("click", function() {
    if (confirm("do you really want to add a new user?")) {
      $.getJSON("service.php", { action: "newuser", username: $("#newusername").attr("value"), password: $("#newuserpassword").attr("value"), domain: $(this).attr("name") }, function(json){
        if (json.error) {
          alert(json.error);
          return false;
        }
        updatedomain();
      });
    }
  });
  $("#newusercancel").live("click", function() {
    $("#newuserbutton").show();
    $("#newuser").hide();
  });
  $(".deleteuser").live("click", function() {
    if (confirm("do you really want to delete the user?")) {
      $.getJSON("service.php", { action: "deleteuser", user: $(this).attr("name") }, function(json){
        if (json.error) {
          alert(json.error);
          return false;
        }
        updatedomain();
      });
    }
  });
  $(".changeuserpassword").live("click", function() {
    if (confirm("do you really want to reset the password?")) {
      $.getJSON("service.php", { action: "changeuserpassword", user: $(this).attr("name"), password: $(this).next("input").attr("value") }, function(json){
        if (json.error) {
          alert(json.error);
          return false;
        }
        alert(json.msg);
        updatedomain();
      });
    }
  });

  // Aliases
  $(".aliasbutton").live("click", function() {
    $(this).next(".editalias").toggle();
    return (false);
  });
  $("#newaliasbutton").live("click", function() {
    $("#newaliasbutton").hide();
    $("#newalias").show();
  });
  $("#newaliassave").live("click", function() {
    if (confirm("do you really want to add a new alias?")) {
      $.getJSON("service.php", { action: "newalias", alias: $("#newaliastext").attr("value"), target: $("#newaliastarget").attr("value"), domain: $(this).attr("name") }, function(json){
        if (json.error) {
          alert(json.error);
          return false;
        }
        updatedomain();
      });
    }
  });
  $("#newaliascancel").live("click", function() {
    $("#newaliasbutton").show();
    $("#newalias").hide();
  });
  $(".deletealias").live("click", function() {
    if (confirm("do you really want to delete the alias?")) {
      $.getJSON("service.php", { action: "deletealias", alias: $(this).attr("name") }, function(json){
        if (json.error) {
          alert(json.error);
          return false;
        }
        updatedomain();
      });
    }
  });
  $(".changealiastarget").live("click", function() {
    if (confirm("do you really want to change the alias target?")) {
      $.getJSON("service.php", { action: "changealiastarget", alias: $(this).attr("name"), target: $(this).next("input").attr("value") }, function(json){
        if (json.error) {
          alert(json.error);
          return false;
        }
        updatedomain();
      });
    }
  });


  function updatedomains() {
    $.get("index.php", { view: "domains" }, function (data) {
      $("#domains").html(data);
    });
  }
  
  function updatedomain() {
    $.get("index.php", { view: "domain", domain: domain }, function (data) {
      $("#content").html(data);
    });
  }

});
