<?php

// Select a job (tier 1) or advance to next tier
function selectJob($db, $data)
{
  $player_name = clean($data['player_name']);
  $room_id = intval(clean($data['room_id']));
  $job = clean($data['job']);
  
  // Validate job exists in database
  $job_check = $db->prepare("SELECT job_id, name, min_level, tier, required_base_job FROM resources_jobs WHERE job_id = ? AND banned = 0");
  $job_check->bind_param("s", $job);
  $job_check->execute();
  $job_result = $job_check->get_result();
  if (mysqli_num_rows($job_result) === 0) {
    $job_check->close();
    return array('err' => 'invalid_job');
  }
  $job_row = mysqli_fetch_array($job_result);
  $job_min_level = intval($job_row['min_level']);
  $job_tier = intval($job_row['tier']);
  $required_base_job = $job_row['required_base_job'];
  $job_check->close();
  
  // Get player
  $sp = $db->prepare("SELECT id, stats FROM game_players WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);
  
  if ($sp->execute()) {
    $rp = $sp->get_result();
    if (mysqli_num_rows($rp) > 0) {
      $row = mysqli_fetch_array($rp);
      $player_id = intval($row["id"]);
      $stats_str = $row["stats"];
      
      // Parse stats
      $lvl=1;$exp=0;$hp=1;$maxhp=1;$mp=0;$maxmp=0;$atk=0;$def=0;$spd=0;$evd=0;$crt=5;$gold=0;$skill_points=0;$current_job='none';$unlocked_skills='';
      $parts = explode(';', $stats_str);
      foreach ($parts as $p) {
        if ($p === '') continue;
        if (str_starts_with($p, 'lvl=')) { $lvl = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'exp=')) { $exp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'hp=')) { $hp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'maxhp=')) { $maxhp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'mp=')) { $mp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'maxmp=')) { $maxmp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'atk=')) { $atk = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'def=')) { $def = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'spd=')) { $spd = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'evd=')) { $evd = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'crt=')) { $crt = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'gold=')) { $gold = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'skill_points=')) { $skill_points = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'job=')) { $current_job = explode('=', $p)[1]; }
        else if (str_starts_with($p, 'unlocked_skills=')) { $unlocked_skills = explode('=', $p)[1]; }
      }
      
      // Check level requirement
      if ($lvl < $job_min_level) {
        return array('err' => 'level_too_low', 'required_level' => $job_min_level);
      }
      
      // Check job tier requirements
      if ($job_tier == 1) {
        // Tier 1: Can only select if no job
        if ($current_job !== 'none') {
          return array('err' => 'already_has_job', 'current_job' => $current_job);
        }
      } else {
        // Tier 2+: Must have correct base job
        if ($current_job === 'none') {
          return array('err' => 'need_base_job', 'required_base_job' => $required_base_job);
        }
        // Check if current job matches required base job
        // For tier 2+, we need to trace back to tier 1
        $base_job = getBaseJob($db, $current_job);
        if ($base_job !== $required_base_job) {
          return array('err' => 'wrong_base_job', 'required_base_job' => $required_base_job, 'your_base_job' => $base_job);
        }
      }
      
      // Update job
      $new_stats = setPlayerStats($lvl, $exp, $hp, $maxhp, $mp, $maxmp, $atk, $def, $spd, $evd, $gold, $crt, $skill_points, $job, $unlocked_skills);
      $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
      $up->bind_param("si", $new_stats, $player_id);
      $up->execute();
      $up->close();
      
      touchPlayer($db, $room_id, $player_name);
      
      return array('success' => true, 'job' => $job);
    }
  }
  $sp->close();
  return array('err' => 'player_not_found');
}

// Unlock a skill using skill points
function unlockSkill($db, $data)
{
  $player_name = clean($data['player_name']);
  $room_id = intval(clean($data['room_id']));
  $skill = clean($data['skill']);
  
  // Get skill definition from database
  $skill_check = $db->prepare("SELECT skill_id, unlock_cost, required_job, required_skills FROM resources_skills WHERE skill_id = ? AND banned = 0");
  $skill_check->bind_param("s", $skill);
  $skill_check->execute();
  $skill_result = $skill_check->get_result();
  if (mysqli_num_rows($skill_result) === 0) {
    $skill_check->close();
    return array('err' => 'invalid_skill');
  }
  $skill_row = mysqli_fetch_array($skill_result);
  $skill_cost = intval($skill_row['unlock_cost']);
  $required_job = $skill_row['required_job'];
  $required_skills_str = $skill_row['required_skills'];
  $skill_check->close();
  
  // Get player
  $sp = $db->prepare("SELECT id, stats FROM game_players WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);
  
  if ($sp->execute()) {
    $rp = $sp->get_result();
    if (mysqli_num_rows($rp) > 0) {
      $row = mysqli_fetch_array($rp);
      $player_id = intval($row["id"]);
      $stats_str = $row["stats"];
      
      // Parse stats
      $lvl=1;$exp=0;$hp=1;$maxhp=1;$mp=0;$maxmp=0;$atk=0;$def=0;$spd=0;$evd=0;$crt=5;$gold=0;$skill_points=0;$job='none';$unlocked_skills='';
      $parts = explode(';', $stats_str);
      foreach ($parts as $p) {
        if ($p === '') continue;
        if (str_starts_with($p, 'lvl=')) { $lvl = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'exp=')) { $exp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'hp=')) { $hp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'maxhp=')) { $maxhp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'mp=')) { $mp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'maxmp=')) { $maxmp = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'atk=')) { $atk = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'def=')) { $def = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'spd=')) { $spd = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'evd=')) { $evd = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'crt=')) { $crt = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'gold=')) { $gold = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'skill_points=')) { $skill_points = intval(explode('=', $p)[1]); }
        else if (str_starts_with($p, 'job=')) { $job = explode('=', $p)[1]; }
        else if (str_starts_with($p, 'unlocked_skills=')) { $unlocked_skills = explode('=', $p)[1]; }
      }
      
      // Check job requirement
      if ($required_job !== 'all' && $job !== $required_job) {
        return array('err' => 'wrong_job', 'required_job' => $required_job, 'current_job' => $job);
      }
      
      // Check if already unlocked
      $unlocked_array = $unlocked_skills === '' ? array() : explode(',', $unlocked_skills);
      if (in_array($skill, $unlocked_array)) {
        return array('err' => 'already_unlocked');
      }
      
      // Check skill prerequisites
      if ($required_skills_str !== null && $required_skills_str !== '') {
        $required_skills_array = explode(',', $required_skills_str);
        $missing_prereqs = array();
        foreach ($required_skills_array as $req_skill) {
          $req_skill = trim($req_skill);
          if ($req_skill !== '' && !in_array($req_skill, $unlocked_array)) {
            $missing_prereqs[] = $req_skill;
          }
        }
        if (count($missing_prereqs) > 0) {
          return array('err' => 'missing_prerequisites', 'required_skills' => $missing_prereqs);
        }
      }
      
      // Check skill points
      if ($skill_points < $skill_cost) {
        return array('err' => 'not_enough_skill_points', 'required' => $skill_cost, 'available' => $skill_points);
      }
      
      // Unlock skill
      $skill_points -= $skill_cost;
      $unlocked_array[] = $skill;
      $unlocked_skills = implode(',', $unlocked_array);
      
      $new_stats = setPlayerStats($lvl, $exp, $hp, $maxhp, $mp, $maxmp, $atk, $def, $spd, $evd, $gold, $crt, $skill_points, $job, $unlocked_skills);
      $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
      $up->bind_param("si", $new_stats, $player_id);
      $up->execute();
      $up->close();
      
      touchPlayer($db, $room_id, $player_name);
      
      return array('success' => true, 'skill' => $skill, 'remaining_points' => $skill_points, 'unlocked_skills' => $unlocked_array);
    }
  }
  $sp->close();
  return array('err' => 'player_not_found');
}

// Helper: Get base tier 1 job from any job
function getBaseJob($db, $job_id)
{
  if ($job_id === 'none') return 'none';
  
  $current = $job_id;
  $max_iterations = 10; // Prevent infinite loops
  $iterations = 0;
  
  while ($iterations < $max_iterations) {
    $query = $db->prepare("SELECT tier, required_base_job FROM resources_jobs WHERE job_id = ? AND banned = 0");
    $query->bind_param("s", $current);
    if ($query->execute()) {
      $result = $query->get_result();
      if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $tier = intval($row['tier']);
        $required = $row['required_base_job'];
        $query->close();
        
        if ($tier == 1) {
          return $current; // Found base job
        }
        if ($required) {
          $current = $required; // Move up the chain
          $iterations++;
          continue;
        }
      }
      $query->close();
    }
    break;
  }
  
  return $job_id; // Fallback to original if can't trace
}

// Get all available jobs
function getJobs($db)
{
  $jobs = array();
  try {
    $query = $db->prepare("SELECT job_id, name, description, stat_modifiers, min_level, tier, required_base_job FROM resources_jobs WHERE banned = 0 ORDER BY tier ASC, min_level ASC, name ASC");
    if ($query && $query->execute()) {
      $result = $query->get_result();
      while ($row = mysqli_fetch_array($result)) {
        $jobs[] = array(
          'job_id' => $row['job_id'],
          'name' => $row['name'],
          'description' => $row['description'],
          'stat_modifiers' => $row['stat_modifiers'],
          'min_level' => intval($row['min_level']),
          'tier' => intval($row['tier']),
          'required_base_job' => $row['required_base_job']
        );
      }
      $query->close();
    }
  } catch (Throwable $_) { }
  return $jobs;
}

// Get all available skills
function getSkills($db)
{
  $skills = array();
  try {
    $query = $db->prepare("SELECT skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills FROM resources_skills WHERE banned = 0 ORDER BY required_job ASC, name ASC");
    if ($query && $query->execute()) {
      $result = $query->get_result();
      while ($row = mysqli_fetch_array($result)) {
        $skills[] = array(
          'skill_id' => $row['skill_id'],
          'name' => $row['name'],
          'description' => $row['description'],
          'mp_cost' => intval($row['mp_cost']),
          'cooldown_sec' => intval($row['cooldown_sec']),
          'damage_multiplier' => floatval($row['damage_multiplier']),
          'unlock_cost' => intval($row['unlock_cost']),
          'required_job' => $row['required_job'],
          'required_skills' => $row['required_skills']
        );
      }
      $query->close();
    }
  } catch (Throwable $_) { }
  return $skills;
}
