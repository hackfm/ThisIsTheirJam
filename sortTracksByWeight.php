<?php

    function sortFunc ($a, $b) {
        if ($a['weight'] == $b['weight']) {
            return 0;
        }
        return ($a['weight'] > $b['weight']) ? -1 : 1;
    }

    function sortTracksByWeight($tracks) {
        usort($tracks, "sortFunc");
        return $tracks;
    }