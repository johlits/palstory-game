<?php

function getMusic()
{
    $baseUrl = getImageUrl("");

    $files = [
        "music/ambient/Ambient_Piano__BPM60 (1).wav",
        "music/ambient/Ambient_Piano__BPM60.wav",
        "music/ambient/Ambient_Piano__BPM72 (1).wav",
        "music/ambient/Ambient_Piano__BPM72.wav",
        "music/ambient/Ambient_Piano__BPM80 (1).wav",
        "music/ambient/Ambient_Piano__BPM80.wav",
        "music/ambient/Ambient_Piano__BPM92 (1).wav",
        "music/ambient/Ambient_Piano__BPM92.wav",
        "music/ambient/Ambient_Piano__BPM98 (1).wav",
        "music/ambient/Ambient_Piano__BPM98.wav",
        "music/ambient/Ambient_Piano__BPM108.wav",
        "music/ambient/Ambient_Piano__BPM120 (1).wav",
        "music/ambient/Ambient_Piano__BPM120.wav",
        "music/ambient/Cinematic_Electro__BPM70.wav",
        "music/ambient/Cinematic_Electro__BPM80.wav"
    ];

    return array_map(fn($file) => $baseUrl . $file, $files);
}

function getResourceInfo($db) {
  $arr = array();

  $sri = $db->prepare("SELECT COUNT(*) FROM resources_items");  
  if ($sri->execute()) {
    $srir = $sri->get_result();
    while ($srirow = mysqli_fetch_array($srir)) {
      array_push($arr, $srirow);
      break;
    }
  }
  $sri->close();

  $srm = $db->prepare("SELECT COUNT(*) FROM resources_monsters");  
  if ($srm->execute()) {
    $srmr = $srm->get_result();
    while ($srmrow = mysqli_fetch_array($srmr)) {
      array_push($arr, $srmrow);
      break;
    }
  }
  $srm->close();

  $srl = $db->prepare("SELECT COUNT(*) FROM resources_locations");  
  if ($srl->execute()) {
    $srlr = $srl->get_result();
    while ($srlrow = mysqli_fetch_array($srlr)) {
      array_push($arr, $srlrow);
      break;
    }
  }
  $srl->close();

  return $arr;
}
