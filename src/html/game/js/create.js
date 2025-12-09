var banItem = function (id, secret) {
  $.ajax({
    url: "createServer.php?ban_item=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "banned") {
        alert("Item banned successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
}

var banMonster = function (id, secret) {
  $.ajax({
    url: "createServer.php?ban_monster=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "banned") {
        alert("Monster banned successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
}

var banLocation = function (id, secret) {
  $.ajax({
    url: "createServer.php?ban_location=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "banned") {
        alert("Location banned successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
}

var deleteItem = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_item=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "deleted") {
        alert("Item deleted successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
}

var deleteMonster = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_monster=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "deleted") {
        alert("Monster deleted successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
}

var deleteLocation = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_location=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "deleted") {
        alert("Location deleted successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
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
    "#item_name,#item_image,#item_description,#item_stats,#item_model,#item_edit_id"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      var trimmedCode = code ? code.trim() : "";
      if (trimmedCode === "updated" || trimmedCode === "created") {
        // Operation executed directly, reload page
        alert("Item " + trimmedCode + " successfully!");
        window.location.reload();
      } else {
        console.error(code);
        showErrorModal(code);
      }
      $("#item_save").prop("disabled", false);
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
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
    "#monster_name,#monster_image,#monster_description,#monster_stats,#monster_model,#monster_edit_id"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      var trimmedCode = code ? code.trim() : "";
      if (trimmedCode === "updated" || trimmedCode === "created") {
        // Operation executed directly, reload page
        alert("Monster " + trimmedCode + " successfully!");
        window.location.reload();
      } else {
        console.error(code);
        showErrorModal(code);
      }
      $("#monster_save").prop("disabled", false);
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
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
    "#location_name,#location_image,#location_description,#location_from,#location_to,#location_stats,#location_model,#location_edit_id"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      var trimmedCode = code ? code.trim() : "";
      if (trimmedCode === "updated" || trimmedCode === "created") {
        // Operation executed directly, reload page
        alert("Location " + trimmedCode + " successfully!");
        window.location.reload();
      } else {
        console.error(code);
        showErrorModal(code);
      }
      $("#location_save").prop("disabled", false);
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
      $("#location_save").prop("disabled", false);
    },
  });
};

// Edit functions to populate forms with existing data
var editLocation = function (id, name, image, description, from, to, stats, model) {
  $("#location_edit_id").val(id);
  $("#location_name").val(name);
  $("#location_image").val(image);
  $("#location_description").val(description);
  $("#location_from").val(from);
  $("#location_to").val(to);
  $("#location_stats").val(stats);
  $("#location_model").val(model || '');
  $("#location_save").text("Update Location");
  // Scroll to form
  $("#create_location_section")[0].scrollIntoView({ behavior: 'smooth' });
};

var editMonster = function (id, name, image, description, stats, model) {
  $("#monster_edit_id").val(id);
  $("#monster_name").val(name);
  $("#monster_image").val(image);
  $("#monster_description").val(description);
  $("#monster_stats").val(stats);
  $("#monster_model").val(model || '');
  $("#monster_save").text("Update Monster");
  // Scroll to form
  $("#create_monsters_section")[0].scrollIntoView({ behavior: 'smooth' });
};

var editItem = function (id, name, image, description, stats, model) {
  $("#item_edit_id").val(id);
  $("#item_name").val(name);
  $("#item_image").val(image);
  $("#item_description").val(description);
  $("#item_stats").val(stats);
  $("#item_model").val(model || '');
  $("#item_save").text("Update Item");
  // Scroll to form
  $("#create_items_section")[0].scrollIntoView({ behavior: 'smooth' });
};

// Modal functions
var showErrorModal = function (error) {
  $("#sqlModalError").text(error);
  $("#sqlModal").fadeIn(200);
};

var closeSqlModal = function () {
  $("#sqlModal").fadeOut(200);
};

// Close modal when clicking outside of it
$(document).on('click', '#sqlModal', function(e) {
  if (e.target.id === 'sqlModal') {
    closeSqlModal();
  }
});

// ========== JOB FUNCTIONS ==========

var banJob = function (id, secret) {
  $.ajax({
    url: "createServer.php?ban_job=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "banned") {
        alert("Job banned successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
};

var deleteJob = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_job=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "deleted") {
        alert("Job deleted successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
};

var saveJob = function () {
  $("#job_save").prop("disabled", true);

  var requestData = $(
    "#job_job_id,#job_name,#job_image,#job_description,#job_stat_modifiers,#job_min_level,#job_tier,#job_required_base_job,#job_edit_id"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      var trimmedCode = code ? code.trim() : "";
      if (trimmedCode === "updated" || trimmedCode === "created") {
        // Operation executed directly, reload page
        alert("Job " + trimmedCode + " successfully!");
        window.location.reload();
      } else {
        console.error(code);
        showErrorModal(code);
      }
      $("#job_save").prop("disabled", false);
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
      $("#job_save").prop("disabled", false);
    },
  });
};

var editJob = function (id, jobId, name, image, description, statModifiers, minLevel, tier, requiredBaseJob) {
  $("#job_edit_id").val(id);
  $("#job_job_id").val(jobId);
  $("#job_name").val(name);
  $("#job_image").val(image || '');
  $("#job_description").val(description);
  $("#job_stat_modifiers").val(statModifiers);
  $("#job_min_level").val(minLevel);
  $("#job_tier").val(tier);
  $("#job_required_base_job").val(requiredBaseJob || '');
  $("#job_save").text("Update Job");
  $("#create_jobs_section")[0].scrollIntoView({ behavior: 'smooth' });
};

// ========== SKILL FUNCTIONS ==========

var banSkill = function (id, secret) {
  $.ajax({
    url: "createServer.php?ban_skill=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "banned") {
        alert("Skill banned successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
};

var deleteSkill = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_skill=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "deleted") {
        alert("Skill deleted successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
};

var saveSkill = function () {
  $("#skill_save").prop("disabled", true);

  var requestData = $(
    "#skill_skill_id,#skill_name,#skill_image,#skill_description,#skill_mp_cost,#skill_cooldown,#skill_damage_multiplier,#skill_unlock_cost,#skill_required_job,#skill_required_skills,#skill_type,#skill_stat_modifiers,#skill_synergy_with,#skill_synergy_bonus,#skill_synergy_window,#skill_edit_id"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      var trimmedCode = code ? code.trim() : "";
      if (trimmedCode === "updated" || trimmedCode === "created") {
        // Operation executed directly, reload page
        alert("Skill " + trimmedCode + " successfully!");
        window.location.reload();
      } else {
        console.error(code);
        showErrorModal(code);
      }
      $("#skill_save").prop("disabled", false);
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
      $("#skill_save").prop("disabled", false);
    },
  });
};

var editSkill = function (id, skillId, name, image, description, mpCost, cooldown, damageMultiplier, unlockCost, requiredJob, requiredSkills, skillType, statModifiers, synergyWith, synergyBonus, synergyWindow) {
  $("#skill_edit_id").val(id);
  $("#skill_skill_id").val(skillId);
  $("#skill_name").val(name);
  $("#skill_image").val(image || '');
  $("#skill_description").val(description);
  $("#skill_mp_cost").val(mpCost);
  $("#skill_cooldown").val(cooldown);
  $("#skill_damage_multiplier").val(damageMultiplier);
  $("#skill_unlock_cost").val(unlockCost);
  $("#skill_required_job").val(requiredJob || 'all');
  $("#skill_required_skills").val(requiredSkills || '');
  $("#skill_type").val(skillType || 'active');
  $("#skill_stat_modifiers").val(statModifiers || '');
  $("#skill_synergy_with").val(synergyWith || '');
  $("#skill_synergy_bonus").val(synergyBonus || '');
  $("#skill_synergy_window").val(synergyWindow || 5);
  $("#skill_save").text("Update Skill");
  $("#create_skills_section")[0].scrollIntoView({ behavior: 'smooth' });
};

// ========== MONSTER SKILL FUNCTIONS ==========

var deleteMonsterSkill = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_monster_skill=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "deleted") {
        alert("Monster skill deleted successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
};

var saveMonsterSkill = function () {
  $("#mskill_save").prop("disabled", true);

  var requestData = $(
    "#mskill_monster_id,#mskill_skill_id,#mskill_image,#mskill_edit_id"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      var trimmedCode = code ? code.trim() : "";
      if (trimmedCode === "updated" || trimmedCode === "created") {
        // Operation executed directly, reload page
        alert("Monster skill " + trimmedCode + " successfully!");
        window.location.reload();
      } else {
        console.error(code);
        showErrorModal(code);
      }
      $("#mskill_save").prop("disabled", false);
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
      $("#mskill_save").prop("disabled", false);
    },
  });
};

var editMonsterSkill = function (id, monsterId, skillId, image) {
  $("#mskill_edit_id").val(id);
  $("#mskill_monster_id").val(monsterId);
  $("#mskill_skill_id").val(skillId);
  $("#mskill_image").val(image || '');
  $("#mskill_save").text("Update Monster Skill");
  $("#create_monster_skills_section")[0].scrollIntoView({ behavior: 'smooth' });
};

// ========== SHOP INVENTORY FUNCTIONS ==========

var deleteShopItem = function (id, secret) {
  $.ajax({
    url: "createServer.php?delete_shop_item=" + id + "&secret=" + secret,
    type: "get",
    dataType: "json",
    success: function (response, status, http) {
      var result = response[0];
      if (result === "deleted") {
        alert("Shop item deleted successfully!");
        window.location.reload();
      } else {
        showErrorModal(result);
      }
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
    },
  });
};

var saveShopItem = function () {
  $("#shop_save").prop("disabled", true);

  var requestData = $(
    "#shop_item_id,#shop_price,#shop_stock_unlimited,#shop_available_level,#shop_category,#shop_edit_id"
  ).serialize();

  $.ajax({
    url: "createServer.php",
    type: "get",
    data: requestData,
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      var code = response[0];
      var trimmedCode = code ? code.trim() : "";
      if (trimmedCode === "updated" || trimmedCode === "created") {
        // Operation executed directly, reload page
        alert("Shop item " + trimmedCode + " successfully!");
        window.location.reload();
      } else {
        console.error(code);
        showErrorModal(code);
      }
      $("#shop_save").prop("disabled", false);
    },
    error: function (http, status, error) {
      console.error(error);
      showErrorModal(error);
      $("#shop_save").prop("disabled", false);
    },
  });
};

var editShopItem = function (id, itemId, price, stockUnlimited, availableLevel, category) {
  $("#shop_edit_id").val(id);
  $("#shop_item_id").val(itemId);
  $("#shop_price").val(price);
  $("#shop_stock_unlimited").val(stockUnlimited);
  $("#shop_available_level").val(availableLevel);
  $("#shop_category").val(category);
  $("#shop_save").text("Update Shop Item");
  $("#create_shop_section")[0].scrollIntoView({ behavior: 'smooth' });
};
