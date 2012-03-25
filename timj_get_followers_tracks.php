<?php

/**
 * The "get followers tracks" part of the This is My Jelly hack.
 *
 * N.B. tracks are always in the form as [<artist>, <track>].
 *
 * @author Sam Smith <samuel.david.smith@gmail.com>
 */

// Configuration
define('FGC_CACHE_DIR', './cache');
define('FOLLOWER_TRACK_LIMIT', 20);
define('LASTFM_API_KEY', '35d38be5f331184e81fb0e7de24b01c1');

$countCacheHits = 0;
$countCacheMisses = 0;

function timj_file_get_contents($filename /*, ... */) {
    $hash = md5($filename);
    $path = FGC_CACHE_DIR . "/$hash";

    global $countCacheHits, $countCacheMisses;

    ++$countCacheHits;

    if (is_file($path)) {
        return file_get_contents($path);
    }

    ++$countCacheMisses;

    $contents = file_get_contents($filename);

    file_put_contents($path, $contents);

    return $contents;
}

/**
 * Gets the TIMJ user's followers.
 *
 * @return array[string]
 */
function timj_get_followers($username) {
    $followersURL = "http://thisismyjam.com/$username/followers";

    $content                = timj_file_get_contents($followersURL);
    $originalErrorReporting = error_reporting(0);

    $document = new DOMDocument();
    $document->loadHTML($content);
    
    $xPath = new DOMXPath($document);

    # Number of pages of followers? (Aren't you popular!)
    $nodes     = $xPath->query("descendant-or-self::*[contains(concat(' ', normalize-space(@class), ' '), ' pageno ')][last()]");
    $pageCount = $nodes->length ? intval($nodes->item(0)->nodeValue) : 1;
    $result    = array();

    for ($i = 0; $i < $pageCount; ++$i) {
        if ($i > 1) {
            $url     = $followersURL . "?page=$i";
            $content = timj_file_get_contents($url);

            $document->loadHTML($content);

            $xPath = new DOMXPath($document); // WTF DOMXPath?  
        }

        // .username
        $nodes  = $xPath->query("descendant-or-self::*[contains(concat(' ', normalize-space(@class), ' '), ' username ')]");
        $length = $nodes->length;

        for ($j = 0; $j < $length; ++$j) {
            $node = $nodes->item($j);

            $result[] = $node->nodeValue;
        }
    }

    error_reporting($originalErrorReporting);

    return $result;
}

/**
 * Gets the TIMJ user's Last.fm username.
 *
 * @return string|boolean The TIMJ user's Last.fm username, otherwise false
 */
function timj_get_lastfm_username($username) {
    $userURL = "http://thisismyjam.com/$username";

    $content                = timj_file_get_contents($userURL);
    $originalErrorReporting = error_reporting(0);

    $document = new DOMDOcument();
    $document->loadHTML($content);

    error_reporting($originalErrorReporting);

    $xPath  = new DOMXPath($document);
    $nodes  = $xPath->query("descendant-or-self::*[contains(concat(' ', normalize-space(@class), ' '), ' smaller ') and (contains(concat(' ', normalize-space(@class), ' '), ' topless '))]/descendant::a");
    $length = $nodes->length;

    for ($i = 0; $i < $length; ++$i) {
        $node = $nodes->item($i);

        if (strcasecmp($node->nodeValue, 'Last.fm') == 0) {
            // Extract "phuedx" from "http://www.last.fm/user/phuedx"
            $href     = $node->getAttribute('href');
            $username = end(explode('/', $href));

            return $username;
        }
    }

    return false;
}

/**
 * Gets the Last.fm user's recently loved tracks.
 *
 * @return array[array[]]
 */
function timj_get_lastfm_loved_tracks($lastFMUsername) {
    $lovedTracksURL = sprintf(
        'http://ws.audioscrobbler.com/2.0/?method=user.getlovedtracks&user=%s&limit=%d&api_key=%s&format=json',
        $lastFMUsername,
        FOLLOWER_TRACK_LIMIT,
        LASTFM_API_KEY
    );

    $contents = timj_file_get_contents($lovedTracksURL);
    $response = json_decode($contents, true);

    if ( ! isset($response['lovedtracks']) || ! isset($response['lovedtracks']['track'])) {
        return array();
    }

    $tracks = $response['lovedtracks']['track'];
    $result = array();

    foreach ($tracks as $track) {
        $result[] = array(
            $track['artist']['name'],
            $track['name'],
        );
    }

    return $result;
}

/**
 * Gets the TIMJ user's recently jammed tracks.
 * 
 * @return array[array[]]
 */
function timj_get_tracks($username) {
    $userURL = "http://thisismyjam.com/$username";

    $content                = timj_file_get_contents($userURL);
    $originalErrorReporting = error_reporting(0);

    $document = new DOMDocument();
    $document->loadHTML($content);

    error_reporting($originalErrorReporting);
    
    $xPath  = new DOMXPath($document);
    $nodes  = $xPath->query("descendant-or-self::*[contains(concat(' ', normalize-space(@class), ' '), ' jamArchive ')]/descendant::h3");
    $length = $nodes->length;
    $result = array();

    for ($i = 0; $i < $length; ++$i) {
        $node                 = $nodes->item($i);
        list($track, $artist) = explode(' by ', $node->nodeValue);
        
        $result[] = array($artist, $track);
    }

    return $result;
}

/**
 * Gets the unique tracks in the set of tracks.
 *
 * The name is a nod to PHP's `array_unique` method.
 *
 * @return array[array[]]
 */
function timj_tracks_unique($tracks) {
    $result = array();

    foreach ($tracks as $track) {
        $hash = md5("{$track[1]} by {$track[0]}");

        $result[$hash] = $track;
    }

    return array_values($result);
}

/**
 * Gets a list of tracks that from the TIMJ user's followers by either scraping
 * their TIMJ user page or getting their loved tracks from Last.fm.
 *
 * @return array[array[]]
 */
function timj_get_followers_tracks($username) {
    $followers = timj_get_followers($username);
    $result    = array();

    foreach ($followers as $follower) {
        // If the follower has a Last.fm account then grab their loved tracks
        $lastFMUsername = timj_get_lastfm_username($follower);

        $tracks = $lastFMUsername
            ? timj_get_lastfm_loved_tracks($lastFMUsername)
            : timj_get_tracks($follower);

        for ($i=0;$i<count($tracks);$i++) {
            $tracks[$i]['username'] = $follower;
        }



        $result = array_merge($result, $tracks);
    }

    $result = timj_tracks_unique($result);

    return $result;
}
