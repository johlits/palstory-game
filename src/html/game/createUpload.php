<?php
// Increase upload limits for large 3D model files
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', '300');

// Check uploads directory
$target_dir = "../uploads/";
if (!file_exists($target_dir)) {
    echo "Creating uploads directory...<br>";
    mkdir($target_dir, 0755, true);
} else {
    echo "Uploads directory exists.<br>";
}

// Check if directory is writable
if (!is_writable($target_dir)) {
    echo "Error: Uploads directory is not writable. Please check permissions.<br>";
    die("Directory permissions error.");
}
?>
<html>

<head>
  <title>Upload Files - Images & 3D Models</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="manifest" href="assets/manifest.json" />

  <link rel="apple-touch-icon" sizes="512x512" href="assets/android/android-launchericon-512-512.png">
  <link rel="apple-touch-icon" sizes="192x192" href="assets/android/android-launchericon-192-192.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/ios/180.png">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/ios/32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/ios/16.png">
  <link rel="shortcut icon" href="assets/favicon.ico">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
</head>

<body>
  <div class="container stack gap-4 p-16">
    <h1 class="title">Upload File</h1>
    <p>Upload images (JPG, PNG, GIF) or 3D models (GLB, FBX, OBJ, GLTF) for game resources</p>
  <?php
  $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
  $uploadOk = 1;
  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

  echo "Target file path: " . $target_file . "<br>";
  echo "Current working directory: " . getcwd() . "<br>";
  echo "Uploads directory absolute path: " . realpath($target_dir) . "<br>";

  // Check if image file is an actual image or one of the supported 3D formats
  if (isset($_POST["submit"])) {
    if (in_array($imageFileType, ["glb", "fbx", "obj", "gltf"])) {
      echo "File is a 3D model (" . strtoupper($imageFileType) . ").<br>";
      $uploadOk = 1;
    } else {
      $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
      if ($check !== false) {
        echo "File is an image - " . $check["mime"] . ".<br>";
        $uploadOk = 1;
      } else {
        echo "File is not an image or supported 3D model file.<br>";
        $uploadOk = 0;
      }
    }
  }

  // Check if file already exists - REMOVED to allow overwriting
  // if (file_exists($target_file)) {
  //   echo "Sorry, file already exists.";
  //   $uploadOk = 0;
  // }

  // Allow certain file formats (images + 3D models)
  if (
    $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" && $imageFileType != "glb" && $imageFileType != "fbx"
    && $imageFileType != "obj" && $imageFileType != "gltf"
  ) {
    echo "Sorry, only JPG, JPEG, PNG, GIF, GLB, FBX, OBJ & GLTF files are allowed.";
    $uploadOk = 0;
  }

  // Check file size (only if file type is allowed)
  if ($uploadOk == 1 && $_FILES["fileToUpload"]["size"] > 50000000) {
    echo "Sorry, your file is too large. Maximum size is 50MB.";
    $uploadOk = 0;
  }

  // Check if $uploadOk is set to 0 by an error
  if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
  } else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
      echo "The file " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " has been uploaded.";
    } else {
      echo "Sorry, there was an error uploading your file.";
    }
  }
  ?>
    <div class="mt-16">
      <a class="nes-btn" href="create.php">Back to Create</a>
    </div>
  </div>
</body>

</html>