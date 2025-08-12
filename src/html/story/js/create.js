var banItem = function (id, secret) {
  $.ajax({
    url: "createServer.php?ban_item=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      window.location.reload();
    },
    error: function (http, status, error) {
      console.error(error);
    },
  });
}

var banMonster = function (id, secret) {
  $.ajax({
    url: "createServer.php?ban_monster=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      window.location.reload();
    },
    error: function (http, status, error) {
      console.error(error);
    },
  });
}

var banLocation = function (id, secret) {
  $.ajax({
    url: "createServer.php?ban_location=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      window.location.reload();
    },
    error: function (http, status, error) {
      console.error(error);
    },
  });
}

var deleteItem = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_item=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      window.location.reload();
    },
    error: function (http, status, error) {
      console.error(error);
    },
  });
}

var deleteMonster = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_monster=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      window.location.reload();
    },
    error: function (http, status, error) {
      console.error(error);
    },
  });
}

var deleteLocation = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_location=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      window.location.reload();
    },
    error: function (http, status, error) {
      console.error(error);
    },
  });
}

var generateItemStats = function () {
  var lvl = parseInt($("#item_generate_level").val());
  var type = $("#item_generate_type").val();
  var wobble = parseInt(lvl / 10);
  var atkFrom = Math.max(lvl - Math.floor(Math.random() * wobble), 0);
  var atkTo = lvl + Math.floor(Math.random() * wobble);
  var atk = atkFrom === atkTo ? atkFrom : atkFrom + "-" + atkTo;
  var defFrom = Math.max(lvl - Math.floor(Math.random() * wobble), 0);
  var defTo = lvl + Math.floor(Math.random() * wobble);
  var def = defFrom === defTo ? defFrom : defFrom + "-" + defTo;
  var spdFrom = Math.max(lvl - Math.floor(Math.random() * wobble), 0);
  var spdTo = lvl + Math.floor(Math.random() * wobble);
  var spd = spdFrom === spdTo ? spdFrom : spdFrom + "-" + spdTo;
  var evdFrom = Math.max(lvl - Math.floor(Math.random() * wobble), 0);
  var evdTo = lvl + Math.floor(Math.random() * wobble);
  var evd = evdFrom === evdTo ? evdFrom : evdFrom + "-" + evdTo;
  $("#item_stats").text("type=" + type + ";atk=" + atk + ";def=" + def + ";spd=" + spd + ";evd=" + evd + ";");
}

var generateMonsterStats = function () {
  var lvl = parseInt($("#monster_generate_level").val());
  var wobble = parseInt(lvl / 2);
  var base = parseInt(5 + Math.pow(lvl, 1.5));
  var atkFrom = Math.max(base - Math.floor(Math.random() * 0), 0);
  var atkTo = base + Math.floor(Math.random() * 0);
  var atk = atkFrom === atkTo ? atkFrom : atkFrom + "-" + atkTo;
  var defFrom = Math.max(base - Math.floor(Math.random() * 0), 0);
  var defTo = base + Math.floor(Math.random() * 0);
  var def = defFrom === defTo ? defFrom : defFrom + "-" + defTo;
  var spdFrom = Math.max(base - Math.floor(Math.random() * 0), 0);
  var spdTo = base + Math.floor(Math.random() * 0);
  var spd = spdFrom === spdTo ? spdFrom : spdFrom + "-" + spdTo;
  var evdFrom = Math.max(base - Math.floor(Math.random() * 0), 0);
  var evdTo = base + Math.floor(Math.random() * 0);
  var evd = evdFrom === evdTo ? evdFrom : evdFrom + "-" + evdTo;
  var hpFrom = Math.max(base * 2 - Math.floor(Math.random() * 0), 0);
  var hpTo = base * 2 + Math.floor(Math.random() * 0);
  var hp = hpFrom === hpTo ? hpFrom : hpFrom + "-" + hpTo;
  var goldFrom = Math.max(base - Math.floor(Math.random() * wobble), 0);
  var goldTo = base + Math.floor(Math.random() * wobble);
  var gold = goldFrom === goldTo ? goldFrom : goldFrom + "-" + goldTo;
  var exp = base;
  $("#monster_stats").text("hp=" + hp + ";atk=" + atk + ";def=" + def + ";spd=" + spd + ";evd=" + evd + ";gold=" + gold + ";exp=" + exp + ";drops=");
}

var saveItem = function () {
  $("#item_save").prop("disabled", true);
  console.log($("#item_name").val());
  console.log($("#item_image").val());
  console.log($("#item_description").val());
  console.log($("#item_stats").val());

  var requestData = $(
    "#item_name,#item_image,#item_description,#item_stats"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      if (code == "ok") {
        window.location.reload();
      }
      else {
        console.error(code);
        $("#item_error").text("ERROR: " + code);
        $("#item_save").prop("disabled", false);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      $("#item_error").text("ERROR: " + error);
      $("#item_save").prop("disabled", false);
    },
  });
};

var saveMonster = function () {
  $("#monster_save").prop("disabled", true);
  console.log($("#monster_name").val());
  console.log($("#monster_image").val());
  console.log($("#monster_description").val());
  console.log($("#monster_stats").val());

  var requestData = $(
    "#monster_name,#monster_image,#monster_description,#monster_stats"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      if (code == "ok") {
        window.location.reload();
      }
      else {
        console.error(code);
        $("#monster_error").text("ERROR: " + code);
        $("#monster_save").prop("disabled", false);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      $("#monster_error").text("ERROR: " + error);
      $("#monster_save").prop("disabled", false);
    },
  });
};

var saveLocation = function () {
  $("#location_save").prop("disabled", true);
  console.log($("#location_name").val());
  console.log($("#location_image").val());
  console.log($("#location_description").val());
  console.log($("#location_from").val());
  console.log($("#location_to").val());
  console.log($("#location_stats").val());

  var requestData = $(
    "#location_name,#location_image,#location_description,#location_from,#location_to,#location_stats"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      if (code == "ok") {
        window.location.reload();
      }
      else {
        console.error(code);
        $("#location_error").text("ERROR: " + code);
        $("#location_save").prop("disabled", false);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      $("#location_error").text("ERROR: " + error);
      $("#location_save").prop("disabled", false);
    },
  });
};
