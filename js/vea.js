/* vea - frontend logic (jQuery 4).
 *
 * All state-changing calls POST to service.php with the CSRF token. Reads
 * fetch the partials from index.php. */

$(function () {
  "use strict";

  var csrf = $('meta[name="csrf-token"]').attr("content") || "";
  var currentDomain = "";
  var currentDomainLabel = "";

  // Send a mutating action to service.php.
  function post(action, data, onSuccess) {
    var payload = $.extend({ action: action, csrf: csrf }, data);
    $.post("service.php", payload)
      .done(function (json) {
        if (json && json.error) {
          window.alert(json.error);
          return;
        }
        if (onSuccess) {
          onSuccess(json);
        }
      })
      .fail(function (xhr) {
        // The session/basic-auth expired: reload so the browser re-prompts.
        if (xhr.status === 401) {
          window.location.reload();
          return;
        }
        var message = "request failed (" + xhr.status + ")";
        try {
          var json = JSON.parse(xhr.responseText);
          if (json && json.error) {
            message = json.error;
          }
        } catch (e) {
          // response was not JSON; keep the generic message
        }
        window.alert(message);
      });
  }

  function updateDomains() {
    $.get("index.php", { view: "domains" }, function (data) {
      $("#domains").html(data);
    });
  }

  function updateDomain() {
    $.get("index.php", { view: "domain", domain: currentDomain }, function (data) {
      $("#content").html(data);
    });
  }

  updateDomains();

  /* --- domains ------------------------------------------------------- */

  $(document).on("click", ".domainbutton", function () {
    // The link text is the rendered (Unicode) form; the stored ASCII/Punycode
    // form is in data-name and is what the backend looks up.
    currentDomain = $(this).attr("data-name") || $(this).text();
    currentDomainLabel = $(this).text();
    updateDomain();
    return false;
  });

  $(document).on("click", "#newdomainbutton", function () {
    $("#newdomainbuttonli").addClass("is-hidden");
    $("#newdomainli").removeClass("is-hidden");
  });

  $(document).on("click", "#newdomaincancel", function () {
    $("#newdomainbuttonli").removeClass("is-hidden");
    $("#newdomainli").addClass("is-hidden");
  });

  $(document).on("focusin", "#newdomaintext", function () {
    if ($(this).val() === "newdomain.com") {
      $(this).val("");
    }
  });

  $(document).on("click", "#newdomainsave", function () {
    if (!window.confirm("do you really want to add a new domain?")) {
      return;
    }
    post("newdomain", { domain: $("#newdomaintext").val() }, function () {
      updateDomains();
    });
  });

  $(document).on("click", "#deletedomainbutton", function () {
    if (!window.confirm("do you really want to delete the domain " + currentDomainLabel + "?")) {
      return;
    }
    post("deletedomain", { domain: $(this).attr("name") }, function () {
      updateDomains();
      $("#content").empty();
    });
  });

  /* --- users --------------------------------------------------------- */

  $(document).on("click", ".userbutton", function () {
    $(this).next(".edituser").toggleClass("is-hidden");
    return false;
  });

  $(document).on("click", "#newuserbutton", function () {
    $("#newuserbutton").addClass("is-hidden");
    $("#newuser").removeClass("is-hidden");
  });

  $(document).on("click", "#newusercancel", function () {
    $("#newuserbutton").removeClass("is-hidden");
    $("#newuser").addClass("is-hidden");
  });

  $(document).on("click", "#newusersave", function () {
    if (!window.confirm("do you really want to add a new user?")) {
      return;
    }
    post("newuser", {
      username: $("#newusername").val(),
      password: $("#newuserpassword").val(),
      domain: $(this).attr("name")
    }, function () {
      updateDomain();
    });
  });

  $(document).on("click", ".deleteuser", function () {
    if (!window.confirm("do you really want to delete the user?")) {
      return;
    }
    post("deleteuser", { user: $(this).attr("name") }, function () {
      updateDomain();
    });
  });

  $(document).on("click", ".changeuserpassword", function () {
    if (!window.confirm("do you really want to reset the password?")) {
      return;
    }
    post("changeuserpassword", {
      user: $(this).attr("name"),
      password: $(this).next("input").val()
    }, function (json) {
      if (json && json.msg) {
        window.alert(json.msg);
      }
      updateDomain();
    });
  });

  /* --- aliases ------------------------------------------------------- */

  $(document).on("click", ".aliasbutton", function () {
    $(this).next(".editalias").toggleClass("is-hidden");
    return false;
  });

  $(document).on("click", "#newaliasbutton", function () {
    $("#newaliasbutton").addClass("is-hidden");
    $("#newalias").removeClass("is-hidden");
  });

  $(document).on("click", "#newaliascancel", function () {
    $("#newaliasbutton").removeClass("is-hidden");
    $("#newalias").addClass("is-hidden");
  });

  $(document).on("click", "#newaliassave", function () {
    if (!window.confirm("do you really want to add a new alias?")) {
      return;
    }
    post("newalias", {
      alias: $("#newaliastext").val(),
      target: $("#newaliastarget").val(),
      domain: $(this).attr("name")
    }, function () {
      updateDomain();
    });
  });

  $(document).on("click", ".deletealias", function () {
    if (!window.confirm("do you really want to delete the alias?")) {
      return;
    }
    post("deletealias", { alias: $(this).attr("name") }, function () {
      updateDomain();
    });
  });

  $(document).on("click", ".changealiastarget", function () {
    if (!window.confirm("do you really want to change the alias target?")) {
      return;
    }
    post("changealiastarget", {
      alias: $(this).attr("name"),
      target: $(this).next("input").val()
    }, function () {
      updateDomain();
    });
  });
});
