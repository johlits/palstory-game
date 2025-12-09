<?
session_start();
require_once "./config.php";
?>
<!DOCTYPE html>
<html>

<head>
  <title>PalStory - Create</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="manifest" href="assets/manifest.json" />

  <link rel="apple-touch-icon" sizes="512x512" href="assets/android/android-launchericon-512-512.png">
  <link rel="apple-touch-icon" sizes="192x192" href="assets/android/android-launchericon-192-192.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/ios/180.png">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/ios/32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/ios/16.png">
  <link rel="shortcut icon" href="assets/favicon.ico">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
  <style>
    body.create-page {
      margin: 0;
      padding: 0;
      width: 100vw;
      overflow-x: hidden;
    }
    .create-page .container {
      max-width: 100vw;
      box-sizing: border-box;
    }
    .create-page .right {
      overflow-x: auto;
    }
    .create-page table {
      max-width: 100%;
      table-layout: fixed;
      word-wrap: break-word;
    }
    .create-page th, .create-page td {
      padding: 8px;
      text-align: left;
      vertical-align: top;
      word-wrap: break-word;
    }
    .create-page button {
      margin: 2px;
      padding: 4px 8px;
      font-size: 12px;
    }
    .create-page button[onclick*="edit"] {
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 3px;
      cursor: pointer;
    }
    .create-page button[onclick*="edit"]:hover {
      background-color: #45a049;
    }
    .sql-modal {
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.85);
    }
    .sql-modal-content {
      background-color: #1a1a1a;
      margin: 5% auto;
      padding: 0;
      border: 1px solid #333;
      border-radius: 8px;
      width: 80%;
      max-width: 700px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }
    .sql-modal-header {
      padding: 16px 20px;
      background-color: #2d2d2d;
      color: #fff;
      border-radius: 8px 8px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #444;
    }
    .sql-modal-header h3 {
      margin: 0;
      font-size: 20px;
      color: #fff;
    }
    .sql-modal-close {
      color: #aaa;
      font-size: 32px;
      font-weight: bold;
      cursor: pointer;
      line-height: 20px;
      transition: color 0.2s;
    }
    .sql-modal-close:hover,
    .sql-modal-close:focus {
      color: #fff;
    }
    .sql-modal-body {
      padding: 20px;
      background-color: #1a1a1a;
    }
    .sql-modal-footer {
      padding: 16px 20px;
      background-color: #2d2d2d;
      border-radius: 0 0 8px 8px;
      text-align: right;
      border-top: 1px solid #444;
    }
    .sql-modal-footer button {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s;
      background-color: #444;
      color: white;
    }
    .sql-modal-footer button:hover {
      background-color: #555;
    }
    .sql-error-box {
      background-color: #2d1a1a;
      border: 1px solid #dc3545;
      border-radius: 4px;
      padding: 12px 16px;
      color: #ff6b6b;
      font-size: 14px;
    }
  </style>
</head>

<body id="top" class="create-page">

<!-- Error Modal -->
<div id="sqlModal" class="sql-modal" style="display: none;">
  <div class="sql-modal-content">
    <div class="sql-modal-header">
      <h3>Error</h3>
      <span class="sql-modal-close" onclick="closeSqlModal()">&times;</span>
    </div>
    <div class="sql-modal-body">
      <div id="sqlModalError" class="sql-error-box"></div>
    </div>
    <div class="sql-modal-footer">
      <button onclick="closeSqlModal()">Close</button>
    </div>
  </div>
</div>

<div class="create-toplink"><a class="ylink" href="#top">Top</a></div>

<?
if (isset($_POST["logout"]) && !empty($_POST['logout'])) {
  $_SESSION['secret'] = '';
}
if (isset($_POST["secret"])) {
  $_SESSION['secret'] = $_POST["secret"];
}

if (!isset($_SESSION['secret']) || ($_SESSION['secret'] != admin_game_hash() && $_SESSION['secret'] != super_admin_game_hash())) {
?>
<div class="create-header">
  <div><a href="<?= base_path('/game/') ?>">Home</a></div>
</div>
<div class="center-abs box">
<form id="loginForm" action="<?= base_path('/game/create.php') ?>" method="post" onsubmit="return hashPassword()">
  <label for="secret_input">Password:</label><br>
  <input type="password" id="secret_input" name="secret_input" value="" autofocus><br/><br/>
  <input type="hidden" id="secret" name="secret" value="">
  <input type="submit" value="Submit">
</form>
</div>
<script>
async function hashPassword() {
  const passwordInput = document.getElementById('secret_input');
  const hashedInput = document.getElementById('secret');
  const password = passwordInput.value;
  
  if (!password) return false;
  
  // Hash the password using SHA-256
  const encoder = new TextEncoder();
  const data = encoder.encode(password);
  const hashBuffer = await crypto.subtle.digest('SHA-256', data);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
  
  hashedInput.value = hashHex;
  passwordInput.value = ''; // Clear plaintext
  return true;
}
</script>
<?
} else {
?>

  <div class="create-header">
    <div><b><a href="<?= base_path('/game/') ?>">Home</a></b></div>
    <div>
      <form name="logoutForm" action="<?= base_path('/game/create.php') ?>" method="post">
        <input class="hidden" type="password" id="logout" name="logout" value="1">
        <a href="javascript:document.forms['logoutForm'].submit()">Log out</a>
      </form>
    </div>
  </div>

  <div>

    <div class="two-col">
      <div class="left">
        <h1>Create</h1>

        <a class="ylink" href="#create_locations_section">Locations</a>
        <a class="ylink" href="#create_monsters_section">Monsters</a>
        <a class="ylink" href="#create_items_section">Items</a>
        <a class="ylink" href="#create_jobs_section">Jobs</a>
        <a class="ylink" href="#create_skills_section">Skills</a>
        <a class="ylink" href="#create_monster_skills_section">Monster Skills</a>
        <a class="ylink" href="#create_shop_section">Shop Inventory</a>

        <div id="create_location_section">
          <h2>Locations</h2>
          <label for="location_name">Location name:</label>
          <br />
          <input type="text" id="location_name" name="location_name" placeholder="Forest" size="40">
          <br />
          <label for="location_image">Location image:</label>
          <br />
          <input type="text" id="location_image" name="location_image" placeholder="forest.png" size="40">
          <br />
          <label for="location_description">Location description:</label>
          <br />
          <textarea id="location_description" name="location_description" rows="4" cols="50"
            placeholder="An agglomeration of lush green dark canopies of adjacent trees which merge into one another."></textarea>
          <br />
          <div id="location_range">
            From <input type="number" id="location_from" name="location_from" min="1" max="999999999" value="1"> to
            <input type="number" id="location_to" name="location_to" min="1" max="999999999" value="5">
          </div>
          <label for="location_stats">Location stats:</label>
          <span class="muted text-sm">spawns=Slime,Shroom;</span><br/>
          <textarea id="location_stats" name="location_stats" rows="4" cols="50">spawns=</textarea>
          <br />
          <label for="location_model">Location 3D model info:</label>
          <input type="text" id="location_model" name="location_model" size="40">
          <input type="hidden" id="location_edit_id" name="location_edit_id" value="">
          <br />
          <button id="location_save" onclick="saveLocation()">Save location</button>
          <span id="location_error" class="text-error"></span>
        </div>

        <div id="create_monsters_section">
          <h2>Monsters</h2>
          <label for="monster_name">Monster name:</label>
          <br />
          <input type="text" id="monster_name" name="monster_name" placeholder="Slime" size="40">
          <br />
          <label for="monster_image">Monster image:</label>
          <br />
          <input type="text" id="monster_image" name="monster_image" placeholder="slime.png" size="40">
          <br />
          <label for="monster_description">Monster description:</label>
          <br />
          <textarea id="monster_description" name="monster_description" rows="4" cols="50"
            placeholder="Slimes are gelatinous monsters that hunt by submerging their prey in their body to be slowly dissolved by strong acids."></textarea>
          <br />
          <label for="monster_stats">Monster stats:</label>
          <span><input class="w-80" type="number" id="monster_generate_level" name="monster_generate_level" value="1"><button onclick="generateMonsterStats()">Generate</button></span>
          <span class="muted text-sm">...;drops=Sword,Shield;</span><br/>
          <textarea id="monster_stats" name="monster_stats" rows="4" cols="50"></textarea>
          <br />
          <label for="monster_model">Monster 3D model info:</label>
          <input type="text" id="monster_model" name="monster_model" size="40">
          <input type="hidden" id="monster_edit_id" name="monster_edit_id" value="">
          <br />
          <button id="monster_save" onclick="saveMonster()">Save monster</button>
          <span id="monster_error" class="text-error"></span>
        </div>

        <div id="create_items_section">
          <h2>Items</h2>
          <label for="item_name">Item name:</label>
          <br />
          <input type="text" id="item_name" name="item_name" placeholder="Sword" size="40">
          <br />
          <label for="item_image">Item image:</label>
          <br />
          <input type="text" id="item_image" name="item_image" placeholder="sword.png" size="40">
          <br />
          <label for="item_description">Item description:</label>
          <br />
          <textarea id="item_description" name="item_description" rows="4" cols="50"
            placeholder="An edged, bladed weapon intended for manual cutting or thrusting."></textarea>
          <br />
          <label for="item_stats">Item stats:</label>
          <span><input class="w-150" type="text" id="item_generate_type" name="item_generate_type" placeholder="type"><input class="w-80" type="number" id="item_generate_level" name="item_generate_level" value="1"><button onclick="generateItemStats()">Generate</button></span>
          <textarea id="item_stats" name="item_stats" rows="4" cols="50"></textarea>
          <br />
          <label for="item_model">Item 3D model info:</label>
          <input type="text" id="item_model" name="item_model" size="40">
          <input type="hidden" id="item_edit_id" name="item_edit_id" value="">
          <br />
          <button id="item_save" onclick="saveItem()">Save item</button>
          <span id="item_error" class="text-error"></span>
        </div>

        <div id="create_jobs_section">
          <h2>Jobs</h2>
          <label for="job_job_id">Job ID:</label>
          <br />
          <input type="text" id="job_job_id" name="job_job_id" placeholder="warrior" size="40">
          <br />
          <label for="job_name">Job name:</label>
          <br />
          <input type="text" id="job_name" name="job_name" placeholder="Warrior" size="40">
          <br />
          <label for="job_image">Job image:</label>
          <br />
          <input type="text" id="job_image" name="job_image" placeholder="warrior.png" size="40">
          <br />
          <label for="job_description">Job description:</label>
          <br />
          <textarea id="job_description" name="job_description" rows="4" cols="50"
            placeholder="Strong melee fighter with high ATK and DEF."></textarea>
          <br />
          <label for="job_stat_modifiers">Stat modifiers:</label>
          <span class="muted text-sm">e.g. +ATK +DEF</span><br/>
          <input type="text" id="job_stat_modifiers" name="job_stat_modifiers" placeholder="+ATK +DEF" size="40">
          <br />
          <label for="job_min_level">Min level:</label>
          <input type="number" id="job_min_level" name="job_min_level" min="1" max="100" value="1">
          <br />
          <label for="job_tier">Tier:</label>
          <select id="job_tier" name="job_tier">
            <option value="1">1 - Base</option>
            <option value="2">2 - Advanced</option>
            <option value="3">3 - Expert</option>
            <option value="4">4 - Master</option>
            <option value="5">5 - Legendary</option>
          </select>
          <br />
          <label for="job_required_base_job">Required base job:</label>
          <input type="text" id="job_required_base_job" name="job_required_base_job" placeholder="warrior (or leave empty)" size="40">
          <input type="hidden" id="job_edit_id" name="job_edit_id" value="">
          <br />
          <button id="job_save" onclick="saveJob()">Save job</button>
          <span id="job_error" class="text-error"></span>
        </div>

        <div id="create_skills_section">
          <h2>Skills</h2>
          <label for="skill_skill_id">Skill ID:</label>
          <br />
          <input type="text" id="skill_skill_id" name="skill_skill_id" placeholder="power_strike" size="40">
          <br />
          <label for="skill_name">Skill name:</label>
          <br />
          <input type="text" id="skill_name" name="skill_name" placeholder="Power Strike" size="40">
          <br />
          <label for="skill_image">Skill image:</label>
          <br />
          <input type="text" id="skill_image" name="skill_image" placeholder="power_strike.png" size="40">
          <br />
          <label for="skill_description">Skill description:</label>
          <br />
          <textarea id="skill_description" name="skill_description" rows="4" cols="50"
            placeholder="Heavy attack for 150% damage."></textarea>
          <br />
          <label for="skill_mp_cost">MP cost:</label>
          <input type="number" id="skill_mp_cost" name="skill_mp_cost" min="0" max="999" value="5">
          <label for="skill_cooldown">Cooldown (sec):</label>
          <input type="number" id="skill_cooldown" name="skill_cooldown" min="0" max="999" value="5">
          <br />
          <label for="skill_damage_multiplier">Damage multiplier:</label>
          <input type="number" id="skill_damage_multiplier" name="skill_damage_multiplier" min="0" max="10" step="0.01" value="1.50">
          <br />
          <label for="skill_unlock_cost">Unlock cost (skill points):</label>
          <input type="number" id="skill_unlock_cost" name="skill_unlock_cost" min="1" max="10" value="1">
          <br />
          <label for="skill_required_job">Required job:</label>
          <input type="text" id="skill_required_job" name="skill_required_job" placeholder="all" size="20" value="all">
          <br />
          <label for="skill_required_skills">Required skills:</label>
          <span class="muted text-sm">comma-separated skill_ids</span><br/>
          <input type="text" id="skill_required_skills" name="skill_required_skills" placeholder="power_strike,shield_bash" size="40">
          <br />
          <label for="skill_type">Skill type:</label>
          <select id="skill_type" name="skill_type">
            <option value="active">Active</option>
            <option value="passive">Passive</option>
          </select>
          <br />
          <label for="skill_stat_modifiers">Stat modifiers (passive only):</label>
          <span class="muted text-sm">e.g. atk=+5;def=+3</span><br/>
          <input type="text" id="skill_stat_modifiers" name="skill_stat_modifiers" placeholder="atk=+5;def=+3" size="40">
          <br />
          <label for="skill_synergy_with">Synergy with:</label>
          <input type="text" id="skill_synergy_with" name="skill_synergy_with" placeholder="skill_id" size="20">
          <br />
          <label for="skill_synergy_bonus">Synergy bonus:</label>
          <span class="muted text-sm">e.g. damage=+50%;status=stun</span><br/>
          <input type="text" id="skill_synergy_bonus" name="skill_synergy_bonus" placeholder="damage=+50%" size="40">
          <br />
          <label for="skill_synergy_window">Synergy window (sec):</label>
          <input type="number" id="skill_synergy_window" name="skill_synergy_window" min="1" max="30" value="5">
          <input type="hidden" id="skill_edit_id" name="skill_edit_id" value="">
          <br />
          <button id="skill_save" onclick="saveSkill()">Save skill</button>
          <span id="skill_error" class="text-error"></span>
        </div>

        <div id="create_monster_skills_section">
          <h2>Monster Skills</h2>
          <label for="mskill_monster_id">Monster:</label>
          <br />
          <select id="mskill_monster_id" name="mskill_monster_id">
            <option value="">-- Select Monster --</option>
            <?php
            $monsters_stmt = $db->prepare("SELECT id, name FROM resources_monsters WHERE banned = 0 ORDER BY name");
            if ($monsters_stmt->execute()) {
              $monsters_result = $monsters_stmt->get_result();
              while ($monster = mysqli_fetch_assoc($monsters_result)) {
                echo '<option value="' . $monster['id'] . '">' . htmlspecialchars($monster['name']) . ' (ID: ' . $monster['id'] . ')</option>';
              }
            }
            $monsters_stmt->close();
            ?>
          </select>
          <br />
          <label for="mskill_skill_id">Skill:</label>
          <br />
          <select id="mskill_skill_id" name="mskill_skill_id">
            <option value="">-- Select Skill --</option>
            <?php
            $skills_stmt = $db->prepare("SELECT skill_id, name FROM resources_skills WHERE banned = 0 ORDER BY name");
            if ($skills_stmt->execute()) {
              $skills_result = $skills_stmt->get_result();
              while ($skill = mysqli_fetch_assoc($skills_result)) {
                echo '<option value="' . htmlspecialchars($skill['skill_id']) . '">' . htmlspecialchars($skill['name']) . ' (' . $skill['skill_id'] . ')</option>';
              }
            }
            $skills_stmt->close();
            ?>
          </select>
          <br />
          <label for="mskill_image">Image (optional):</label>
          <br />
          <input type="text" id="mskill_image" name="mskill_image" placeholder="monster_skill.png" size="40">
          <input type="hidden" id="mskill_edit_id" name="mskill_edit_id" value="">
          <br />
          <button id="mskill_save" onclick="saveMonsterSkill()">Save monster skill</button>
          <span id="mskill_error" class="text-error"></span>
        </div>

        <div id="create_shop_section">
          <h2>Shop Inventory</h2>
          <label for="shop_item_id">Item:</label>
          <br />
          <select id="shop_item_id" name="shop_item_id">
            <option value="">-- Select Item --</option>
            <?php
            $items_stmt = $db->prepare("SELECT name FROM resources_items WHERE banned = 0 ORDER BY name");
            if ($items_stmt->execute()) {
              $items_result = $items_stmt->get_result();
              while ($item = mysqli_fetch_assoc($items_result)) {
                echo '<option value="' . htmlspecialchars($item['name']) . '">' . htmlspecialchars($item['name']) . '</option>';
              }
            }
            $items_stmt->close();
            ?>
          </select>
          <br />
          <label for="shop_price">Price (gold):</label>
          <input type="number" id="shop_price" name="shop_price" min="1" max="999999" value="100">
          <br />
          <label for="shop_stock_unlimited">Unlimited stock:</label>
          <select id="shop_stock_unlimited" name="shop_stock_unlimited">
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
          <br />
          <label for="shop_available_level">Available at level:</label>
          <input type="number" id="shop_available_level" name="shop_available_level" min="1" max="100" value="1">
          <br />
          <label for="shop_category">Category:</label>
          <select id="shop_category" name="shop_category">
            <option value="general">General</option>
            <option value="weapon">Weapon</option>
            <option value="armor">Armor</option>
            <option value="shield">Shield</option>
            <option value="consumable">Consumable</option>
          </select>
          <input type="hidden" id="shop_edit_id" name="shop_edit_id" value="">
          <br />
          <button id="shop_save" onclick="saveShopItem()">Save shop item</button>
          <span id="shop_error" class="text-error"></span>
        </div>

      </div>
      <div class="right">
        <h1>Resources</h1>

        <?
        require_once "./config.php";
        ?>
        <a class="ylink" href="#locations_section">Locations</a>
        <a class="ylink" href="#monsters_section">Monsters</a>
        <a class="ylink" href="#items_section">Items</a>
        <a class="ylink" href="#jobs_section">Jobs</a>
        <a class="ylink" href="#skills_section">Skills</a>
        <a class="ylink" href="#monster_skills_section">Monster Skills</a>
        <a class="ylink" href="#shop_section">Shop Inventory</a>

        <h2>Upload Files</h2>
        <p>Upload images or 3D models for game resources</p>

        <form action="createUpload.php" method="post" enctype="multipart/form-data">
          Select file to upload:
          <input type="file" name="fileToUpload" id="fileToUpload">
          <input type="submit" value="Upload File" name="submit">
        </form>

        <div class="mt-16">
          <a href="files.php" class="nes-btn" target="_blank">View All Uploaded Images</a>
        </div>

        <h2 id="locations_section">Locations</h2>
        <table>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Image</th>
            <th>Description</th>
            <th>From</th>
            <th>To</th>
            <th>Stats</th>
            <th>3D Model</th>
            <th>Banned</th>
          </tr>
          <?php
          $selectstmt = $db->prepare("SELECT * FROM resources_locations");
          $arr = array();
          if ($selectstmt->execute()) {
            $result = $selectstmt->get_result();
            $row_count = mysqli_num_rows($result);
            if ($row_count > 0) {
              while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                  <td>
                    <? echo $row[0]; ?>
                  </td>
                  <td>
                    <? echo $row[1]; ?>
                  </td>
                  <td><a href="<?= getImageUrl($row[2]) ?>" target="_blank">View</a></td>
                  <td>
                    <div title="<? echo $row[3]; ?>"><? echo substr($row[3], 0, 10) . "..."; ?></div>
                  </td>
                  <td>
                    <? echo $row[4]; ?>
                  </td>
                  <td>
                    <? echo $row[5]; ?>
                  </td>
                  <td>
                    <div title="<? echo $row[6]; ?>"><? echo substr($row[6], 0, 10) . "..."; ?></div>
                  </td>
                  <td>
                    <? if (!empty($row[7])) { ?>
                      <a href="<?= getImageUrl($row[7]) ?>" target="_blank">View 3D</a>
                    <? } else { ?>
                      <span class="muted">None</span>
                    <? } ?>
                  </td>
                  <td>
                    <? echo $row[8] == 1 ? "yes" : "no"; ?>
                    <button onclick='editLocation(<?= (int)$row[0] ?>, <?= json_encode($row[1]) ?>, <?= json_encode($row[2]) ?>, <?= json_encode($row[3]) ?>, <?= json_encode($row[4]) ?>, <?= json_encode($row[5]) ?>, <?= json_encode($row[6]) ?>, <?= json_encode($row[7]) ?>)'>Edit</button>
                    <? if ($row[8] == 0 && $_SESSION['secret'] == super_admin_game_hash()) { ?>
                      <button onclick="banLocation(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Ban</button>
                    <? } ?>
                    <? if ($row[8] == 1 && $_SESSION['secret'] == admin_game_hash()) { ?>
                      <button onclick="deleteLocation(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Delete</button>
                    <? } ?>
                  </td>
                </tr>
              <?
              }
            }
          }
          $selectstmt->close();
          ?>
        </table>

        <h2 id="monsters_section">Monsters</h2>
        <table>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Image</th>
            <th>Description</th>
            <th>Stats</th>
            <th>3D Model</th>
            <th>Banned</th>
          </tr>
          <?php
          $selectstmt = $db->prepare("SELECT * FROM resources_monsters");
          $arr = array();
          if ($selectstmt->execute()) {
            $result = $selectstmt->get_result();
            $row_count = mysqli_num_rows($result);
            if ($row_count > 0) {
              while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                  <td>
                    <? echo $row[0]; ?>
                  </td>
                  <td>
                    <? echo $row[1]; ?>
                  </td>
                  <td><a href="<?= getImageUrl($row[2]) ?>" target="_blank">View</a></td>
                  <td>
                    <div title="<? echo $row[3]; ?>"><? echo substr($row[3], 0, 10) . "..."; ?></div>
                  </td>
                  <td>
                    <div title="<? echo $row[4]; ?>"><? echo substr($row[4], 0, 10) . "..."; ?></div>
                  </td>
                  <td>
                    <? if (!empty($row[5])) { ?>
                      <a href="<?= getImageUrl($row[5]) ?>" target="_blank">View 3D</a>
                    <? } else { ?>
                      <span class="muted">None</span>
                    <? } ?>
                  </td>
                  <td>
                    <? echo $row[6] == 1 ? "yes" : "no"; ?>
                    <button onclick='editMonster(<?= (int)$row[0] ?>, <?= json_encode($row[1]) ?>, <?= json_encode($row[2]) ?>, <?= json_encode($row[3]) ?>, <?= json_encode($row[4]) ?>, <?= json_encode($row[5]) ?>)'>Edit</button>
                    <? if ($row[6] == 0 && $_SESSION['secret'] == super_admin_game_hash()) { ?>
                      <button onclick="banMonster(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Ban</button>
                    <? } ?>
                    <? if ($row[6] == 1 && $_SESSION['secret'] == admin_game_hash()) { ?>
                      <button onclick="deleteMonster(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Delete</button>
                    <? } ?>
                  </td>
                </tr>
              <?
              }
            }
          }
          $selectstmt->close();
          ?>
        </table>

        <h2 id="items_section">Items</h2>
        <table>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Image</th>
            <th>Description</th>
            <th>Stats</th>
            <th>3D Model</th>
            <th>Banned</th>
          </tr>
          <?php
          $selectstmt = $db->prepare("SELECT * FROM resources_items");
          $arr = array();
          if ($selectstmt->execute()) {
            $result = $selectstmt->get_result();
            $row_count = mysqli_num_rows($result);
            if ($row_count > 0) {
              while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                  <td>
                    <? echo $row[0]; ?>
                  </td>
                  <td>
                    <? echo $row[1]; ?>
                  </td>
                  <td><a href="<?= getImageUrl($row[2]) ?>" target="_blank">View</a></td>
                  <td>
                    <div title="<? echo $row[3]; ?>"><? echo substr($row[3], 0, 10) . "..."; ?></div>
                  </td>
                  <td>
                    <div title="<? echo $row[4]; ?>"><? echo substr($row[4], 0, 10) . "..."; ?></div>
                  </td>
                  <td>
                    <? if (!empty($row[5])) { ?>
                      <a href="<?= getImageUrl($row[5]) ?>" target="_blank">View 3D</a>
                    <? } else { ?>
                      <span class="muted">None</span>
                    <? } ?>
                  </td>
                  <td>
                    <? echo $row[6] == 1 ? "yes" : "no"; ?>
                    <button onclick='editItem(<?= (int)$row[0] ?>, <?= json_encode($row[1]) ?>, <?= json_encode($row[2]) ?>, <?= json_encode($row[3]) ?>, <?= json_encode($row[4]) ?>, <?= json_encode($row[5]) ?>)'>Edit</button>
                    <? if ($row[6] == 0 && $_SESSION['secret'] == super_admin_game_hash()) { ?>
                      <button onclick="banItem(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Ban</button>
                    <? } ?>
                    <? if ($row[6] == 1 && $_SESSION['secret'] == admin_game_hash()) { ?>
                      <button onclick="deleteItem(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Delete</button>
                    <? } ?>
                  </td>
                </tr>
              <?
              }
            }
          }
          $selectstmt->close();
          ?>
        </table>

        <h2 id="jobs_section">Jobs</h2>
        <table>
          <tr>
            <th>ID</th>
            <th>Job ID</th>
            <th>Name</th>
            <th>Image</th>
            <th>Description</th>
            <th>Stat Mods</th>
            <th>Min Lvl</th>
            <th>Tier</th>
            <th>Req Job</th>
            <th>Banned</th>
          </tr>
          <?php
          $selectstmt = $db->prepare("SELECT * FROM resources_jobs ORDER BY tier, name");
          if ($selectstmt->execute()) {
            $result = $selectstmt->get_result();
            $row_count = mysqli_num_rows($result);
            if ($row_count > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= $row['job_id'] ?></td>
                  <td><?= $row['name'] ?></td>
                  <td><? if (!empty($row['image'])) { ?><a href="<?= getImageUrl($row['image']) ?>" target="_blank">View</a><? } else { ?><span class="muted">-</span><? } ?></td>
                  <td><div title="<?= htmlspecialchars($row['description']) ?>"><?= substr($row['description'], 0, 20) ?>...</div></td>
                  <td><?= $row['stat_modifiers'] ?></td>
                  <td><?= $row['min_level'] ?></td>
                  <td><?= $row['tier'] ?></td>
                  <td><?= $row['required_base_job'] ?: '-' ?></td>
                  <td>
                    <?= $row['banned'] == 1 ? "yes" : "no" ?>
                    <button onclick='editJob(<?= (int)$row["id"] ?>, <?= json_encode($row["job_id"]) ?>, <?= json_encode($row["name"]) ?>, <?= json_encode($row["image"]) ?>, <?= json_encode($row["description"]) ?>, <?= json_encode($row["stat_modifiers"]) ?>, <?= (int)$row["min_level"] ?>, <?= (int)$row["tier"] ?>, <?= json_encode($row["required_base_job"]) ?>)'>Edit</button>
                    <? if ($row['banned'] == 0 && $_SESSION['secret'] == super_admin_game_hash()) { ?>
                      <button onclick="banJob(<?= $row['id'] ?>, '<?= $_SESSION['secret'] ?>')">Ban</button>
                    <? } ?>
                    <? if ($row['banned'] == 1 && $_SESSION['secret'] == admin_game_hash()) { ?>
                      <button onclick="deleteJob(<?= $row['id'] ?>, '<?= $_SESSION['secret'] ?>')">Delete</button>
                    <? } ?>
                  </td>
                </tr>
              <?
              }
            }
          }
          $selectstmt->close();
          ?>
        </table>

        <h2 id="skills_section">Skills</h2>
        <table>
          <tr>
            <th>ID</th>
            <th>Skill ID</th>
            <th>Name</th>
            <th>Image</th>
            <th>Description</th>
            <th>MP</th>
            <th>CD</th>
            <th>Dmg</th>
            <th>Cost</th>
            <th>Job</th>
            <th>Type</th>
            <th>Banned</th>
          </tr>
          <?php
          $selectstmt = $db->prepare("SELECT * FROM resources_skills ORDER BY required_job, name");
          if ($selectstmt->execute()) {
            $result = $selectstmt->get_result();
            $row_count = mysqli_num_rows($result);
            if ($row_count > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= $row['skill_id'] ?></td>
                  <td><?= $row['name'] ?></td>
                  <td><? if (!empty($row['image'])) { ?><a href="<?= getImageUrl($row['image']) ?>" target="_blank">View</a><? } else { ?><span class="muted">-</span><? } ?></td>
                  <td><div title="<?= htmlspecialchars($row['description']) ?>"><?= substr($row['description'], 0, 15) ?>...</div></td>
                  <td><?= $row['mp_cost'] ?></td>
                  <td><?= $row['cooldown_sec'] ?></td>
                  <td><?= $row['damage_multiplier'] ?></td>
                  <td><?= $row['unlock_cost'] ?></td>
                  <td><?= $row['required_job'] ?></td>
                  <td><?= $row['skill_type'] ?></td>
                  <td>
                    <?= $row['banned'] == 1 ? "yes" : "no" ?>
                    <button onclick='editSkill(<?= (int)$row["id"] ?>, <?= json_encode($row["skill_id"]) ?>, <?= json_encode($row["name"]) ?>, <?= json_encode($row["image"]) ?>, <?= json_encode($row["description"]) ?>, <?= (int)$row["mp_cost"] ?>, <?= (int)$row["cooldown_sec"] ?>, <?= floatval($row["damage_multiplier"]) ?>, <?= (int)$row["unlock_cost"] ?>, <?= json_encode($row["required_job"]) ?>, <?= json_encode($row["required_skills"]) ?>, <?= json_encode($row["skill_type"]) ?>, <?= json_encode($row["stat_modifiers"]) ?>, <?= json_encode($row["synergy_with"]) ?>, <?= json_encode($row["synergy_bonus"]) ?>, <?= (int)$row["synergy_window_sec"] ?>)'>Edit</button>
                    <? if ($row['banned'] == 0 && $_SESSION['secret'] == super_admin_game_hash()) { ?>
                      <button onclick="banSkill(<?= $row['id'] ?>, '<?= $_SESSION['secret'] ?>')">Ban</button>
                    <? } ?>
                    <? if ($row['banned'] == 1 && $_SESSION['secret'] == admin_game_hash()) { ?>
                      <button onclick="deleteSkill(<?= $row['id'] ?>, '<?= $_SESSION['secret'] ?>')">Delete</button>
                    <? } ?>
                  </td>
                </tr>
              <?
              }
            }
          }
          $selectstmt->close();
          ?>
        </table>

        <h2 id="monster_skills_section">Monster Skills</h2>
        <table>
          <tr>
            <th>ID</th>
            <th>Monster</th>
            <th>Skill</th>
            <th>Image</th>
            <th>Actions</th>
          </tr>
          <?php
          $selectstmt = $db->prepare("SELECT ms.id, ms.monster_resource_id, ms.skill_id, ms.image, rm.name as monster_name, rs.name as skill_name 
                                      FROM monster_skills ms 
                                      LEFT JOIN resources_monsters rm ON ms.monster_resource_id = rm.id 
                                      LEFT JOIN resources_skills rs ON ms.skill_id = rs.skill_id 
                                      ORDER BY rm.name, rs.name");
          if ($selectstmt->execute()) {
            $result = $selectstmt->get_result();
            $row_count = mysqli_num_rows($result);
            if ($row_count > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['monster_name'] ?: 'Unknown') ?> (<?= $row['monster_resource_id'] ?>)</td>
                  <td><?= htmlspecialchars($row['skill_name'] ?: 'Unknown') ?> (<?= $row['skill_id'] ?>)</td>
                  <td><? if (!empty($row['image'])) { ?><a href="<?= getImageUrl($row['image']) ?>" target="_blank">View</a><? } else { ?><span class="muted">-</span><? } ?></td>
                  <td>
                    <button onclick='editMonsterSkill(<?= (int)$row["id"] ?>, <?= (int)$row["monster_resource_id"] ?>, <?= json_encode($row["skill_id"]) ?>, <?= json_encode($row["image"]) ?>)'>Edit</button>
                    <? if ($_SESSION['secret'] == admin_game_hash()) { ?>
                      <button onclick="deleteMonsterSkill(<?= $row['id'] ?>, '<?= $_SESSION['secret'] ?>')">Delete</button>
                    <? } ?>
                  </td>
                </tr>
              <?
              }
            }
          }
          $selectstmt->close();
          ?>
        </table>

        <h2 id="shop_section">Shop Inventory</h2>
        <table>
          <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Req Lvl</th>
            <th>Category</th>
            <th>Actions</th>
          </tr>
          <?php
          $selectstmt = $db->prepare("SELECT * FROM shop_inventory ORDER BY category, item_id");
          if ($selectstmt->execute()) {
            $result = $selectstmt->get_result();
            $row_count = mysqli_num_rows($result);
            if ($row_count > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['item_id']) ?></td>
                  <td><?= $row['price'] ?></td>
                  <td><?= $row['stock_unlimited'] ? 'Unlimited' : 'Limited' ?></td>
                  <td><?= $row['available_at_level'] ?></td>
                  <td><?= $row['category'] ?></td>
                  <td>
                    <button onclick='editShopItem(<?= (int)$row["id"] ?>, <?= json_encode($row["item_id"]) ?>, <?= (int)$row["price"] ?>, <?= (int)$row["stock_unlimited"] ?>, <?= (int)$row["available_at_level"] ?>, <?= json_encode($row["category"]) ?>)'>Edit</button>
                    <? if ($_SESSION['secret'] == admin_game_hash()) { ?>
                      <button onclick="deleteShopItem(<?= $row['id'] ?>, '<?= $_SESSION['secret'] ?>')">Delete</button>
                    <? } ?>
                  </td>
                </tr>
              <?
              }
            }
          }
          $selectstmt->close();
          ?>
        </table>

        <?
        mysqli_close($db);
        ?>


      </div>
    </div>

  </div>
<?
}
?>
</body>

</html>
<!-- jQuery 3.7.1 (upgraded for security) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>window.jQuery || document.write('<script src="js/vendor/jquery-3.7.1.min.js"><\\/script>')</script>
<script src="js/create.js"></script>