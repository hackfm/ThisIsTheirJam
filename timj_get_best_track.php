<?php

/**
 * The "get the best weighted track" part of the This is My Jelly hack.
 *
 * N.B. tracks are always in the form as [<artist>, <track>].
 *
 * @author Sam Smith <samuel.david.smith@gmail.com>
 */

/**
 * Weights the tracks according to the number of occurances of the track in the
 * set.
 *
 * @param  array[array[]]
 * @return array[array[]]
 */
function timj_weight_tracks($tracks) {
    $groups  = array();
    $maxSize = 0;

    foreach ($tracks as $track) {
        $hash                   = md5(strtolower("{$track[1]} by {$track[0]}"));
        $groups[$hash][] = $track;
        
        if (count($groups[$hash]) > $maxSize) {
            $maxSize = count($groups[$hash]);
        }
    }

    $result = array();

    foreach ($groups as $group) {
        $track           = $group[0];
        $count = count($group);
        $track['usernames'] = array();
        foreach ($group as $groupMember) {
            if (in_array($groupMember['username'], $track['usernames'])) {
                --$count;
            } else {
                $track['usernames'][] = $groupMember['username'];
            }
        }
        $track['weight'] = $count / $maxSize;
        $result[] = $track;
    }

    return $result;
}

/**
 * Gets the "best" track from the set of tracks.
 *
 * @param  array[array[]]
 * @return array[] A single, solitary, track... ALONE!!!
 */
function timj_get_best_track($tracks) {
    $weightedTracks = timj_weight_tracks($tracks);

    usort($weightedTracks, function($left, $right) {
        if ($left['weight'] == $right['weight']) {
            return 0;
        }

        return $left['weight'] < $right['weight'] ? -1 : 1;
    });

    $bestTrack = end($weightedTracks);

    return $bestTrack;
}
