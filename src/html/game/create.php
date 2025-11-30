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
    .sql-field {
      font-family: 'Courier New', monospace !important;
      background-color: #f8f9fa !important;
      border: 1px solid #dee2e6 !important;
      border-radius: 4px !important;
      padding: 8px !important;
      font-size: 12px !important;
      line-height: 1.4 !important;
      color: #495057 !important;
      resize: vertical !important;
    }
    .sql-field:focus {
      outline: none !important;
      border-color: #007bff !important;
      box-shadow: 0 0 0 2px rgba(0,123,255,0.25) !important;
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
    .sql-modal-body p {
      margin-top: 0;
      margin-bottom: 10px;
      color: #aaa;
    }
    .sql-modal-body textarea {
      width: 100%;
      box-sizing: border-box;
      background-color: #0d0d0d !important;
      color: #0f0 !important;
      border: 1px solid #333 !important;
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
      margin-left: 8px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s;
    }
    .sql-modal-footer button:first-child {
      background-color: #28a745;
      color: white;
    }
    .sql-modal-footer button:first-child:hover {
      background-color: #218838;
      box-shadow: 0 2px 8px rgba(40,167,69,0.4);
    }
    .sql-modal-footer button:last-child {
      background-color: #444;
      color: white;
    }
    .sql-modal-footer button:last-child:hover {
      background-color: #555;
    }
  </style>
</head>

<body id="top" class="create-page">

<!-- SQL Modal -->
<div id="sqlModal" class="sql-modal" style="display: none;">
  <div class="sql-modal-content">
    <div class="sql-modal-header">
      <h3>Generated SQL</h3>
      <span class="sql-modal-close" onclick="closeSqlModal()">&times;</span>
    </div>
    <div class="sql-modal-body">
      <p>Copy this SQL to your migration file:</p>
      <textarea id="sqlModalText" class="sql-field" rows="8" readonly></textarea>
    </div>
    <div class="sql-modal-footer">
      <button onclick="copySqlToClipboard()">Copy to Clipboard</button>
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

if (!isset($_SESSION['secret']) || ($_SESSION['secret'] != admin_game() && $_SESSION['secret'] != super_admin_game())) {
?>
<div class="create-header">
  <div><a href="<?= base_path('/game/') ?>">Home</a></div>
</div>
<div class="center-abs box">
<form action="<?= base_path('/game/create.php') ?>" method="post">
  <label for="secret">Password:</label><br>
  <input type="password" id="secret" name="secret" value="" autofocus><br/><br/>
  <input type="submit" value="Submit">
</form>
</div>
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

      </div>
      <div class="right">
        <h1>Resources</h1>

        <?
        require_once "./config.php";
        ?>
        <a class="ylink" href="#locations_section">Locations</a>
        <a class="ylink" href="#monsters_section">Monsters</a>
        <a class="ylink" href="#items_section">Items</a>

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
                    <? if ($row[8] == 0 && $_SESSION['secret'] == super_admin_game()) { ?>
                      <button onclick="banLocation(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Ban</button>
                    <? } ?>
                    <? if ($row[8] == 1 && $_SESSION['secret'] == admin_game()) { ?>
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
                    <? if ($row[6] == 0 && $_SESSION['secret'] == super_admin_game()) { ?>
                      <button onclick="banMonster(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Ban</button>
                    <? } ?>
                    <? if ($row[6] == 1 && $_SESSION['secret'] == admin_game()) { ?>
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
                    <? if ($row[6] == 0 && $_SESSION['secret'] == super_admin_game()) { ?>
                      <button onclick="banItem(<? echo $row[0]; ?>, '<? echo $_SESSION['secret'] ?>')">Ban</button>
                    <? } ?>
                    <? if ($row[6] == 1 && $_SESSION['secret'] == admin_game()) { ?>
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