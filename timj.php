<?php
    
   require_once 'timj_get_followers_tracks.php';
   require_once 'getSimiliarTracksForTracks.php';
   require_once 'timj_get_best_track.php';
   require_once 'sortTracksByWeight.php';

   // Allow any domain to make x-domain requests to the API
    header('Access-Control-Allow-Origin: *');

    // We support GET and POST as part of the API and we enable OPTIONS
    // for x-domain preflight requests (see CORS specification)
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

    // Let's not get hammered with preflight requests unnecessarily (cache for a day)
    header('Access-Control-Max-Age: 86400');

    // If the requester wishes to set random headers (e.g. Prototype likes to add X-Prototype-Version)
    // we MUST echo them here or the request will be restricted by the browser. And, no, we can't
    // use * as in the Access-Control-Allow-Origin header.
    if(isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
    }

    $username = $_GET['username'];
    

   $tracks = timj_get_followers_tracks($username);

   shuffle($tracks);

   $tracks = array_slice($tracks, 0, 200);

   $tracks = getSimiliarTracksForTracks($tracks);

   $tracks = timj_weight_tracks($tracks);

   $tracks = sortTracksByWeight($tracks);

   $track = $tracks[0];

   $track['artist'] = $track[0];
   unset($track[0]);
   $track['track'] = $track[1];
   unset($track[1]);
   unset($track['username']);

   $track['cache']['hits'] = $countCacheHits;
   $track['cache']['misses'] = $countCacheMisses;

   header('Content-type: application/json');



   die(json_encode($track));