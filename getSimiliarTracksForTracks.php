<?php

    $echoNestRateLimit = -1; 

    /**
     * Parse echonest rate limit header
     */
    function enLimit() {
        global $echoNestRateLimit, $http_response_header;
        if (isset($http_response_header) && isset($http_response_header[9])) {
            $line = split(': ', $http_response_header[9]);
            $echoNestRateLimit = $line[1]; 
        }
    }   

    /**
     * Take a list of tracks and return a longer list of tracks
     */
    function getSimiliarTracksForTracks($tracks) {
        
        $return = array();
        while (count($tracks) > 5) {
            $part = array_slice($tracks, 0, 5);
            $tracks = array_slice($tracks, 5);
            $return = array_merge($return, getSimiliarTracksForTracks($part));
        }

        // $tracks should be <= 5
        $trackIds = array();
        foreach ($tracks as $track) {
            $title = urlencode($track[1]);
            $artist = urlencode($track[0]);
            $username = $track['username'];
            $song_url = "http://developer.echonest.com/api/v4/song/search?api_key=N6E4NIOVYMTHNDM8J&format=json&artist=$artist&title=$title&results=100";
            $res = json_decode(timj_file_get_contents($song_url), true);
            if (isset($res['response']['songs'][0])) {
                $trackId = $res['response']['songs'][0]['id'];
                $trackIds[] = $trackId;
                enLimit();

                // get tracks for ids
                $radioURL = "http://developer.echonest.com/api/v4/playlist/static?api_key=N6E4NIOVYMTHNDM8J&song_id=$trackId&format=json&results=20&type=song-radio";

                $res = json_decode(timj_file_get_contents($radioURL), true);

                $res2 = array();

                if (isset($res['response']['songs'])) {
                    $res = $res['response']['songs'];

                    foreach ($res as $resE) {
                        $thing = array($resE['artist_name'], $resE['title']);
                        $thing['username'] = $username;
                        $res2[] = $thing;

                    }

                    $return = array_merge($return, $res2);
                }
            } 

            
        }
 
        return $return;
    }
