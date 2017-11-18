<?php

class ilMatterhornUserTracking
{

    const DATATABLE = 'rep_robj_xmh_views';

    /**
     * Adds User-Tracking Data to Database
     *
     * @param int $user_id
     *            the user id for the tracking data
     * @param ilMatterhornEpisode $episode
     *            the video that has been viewed
     * @param int $intime
     *            the start of the time periode of the view
     * @param int $outtime
     *            the end of time period of the view
     */
    public static function putUserTracking($user_id, $episode, $intime, $outtime)
    {
        $view = self::getLastView($user_id, $episode->getEpisodeId());
        
        if ($intime < 0) {
            // do nothing, if this is the first view of this episode from the user, -1 is added automatically
        } else {
            if ($view['intime'] < 0) {
                // first FOOTPRINT after -1
                $view['intime'] = $intime;
                $view['outtime'] = $outtime;
            } else {
                if ($view['outtime'] == $intime) {
                    // same view
                    $view['outtime'] = $outtime;
                } else {
                    $view = [
                        'intime' => $intime,
                        'outtime' => $outtime
                    ];
                }
            }
        }
        
        self::addView($user_id, $episode->getEpisodeId(), $view);
    }

    /**
     * Get the last view from this video from this user
     *
     * @param int $user_id            
     * @param string $episode_id            
     * @return array view
     * @access private
     */
    private static function getLastView($user_id, $episode_id)
    {
        global $ilDB;
        
        $query = $ilDB->query("SELECT id, intime, outtime FROM " . self::DATATABLE . " WHERE user_id = " . $ilDB->quote($user_id, "integer") . " AND episode_id LIKE " . $ilDB->quote($episode_id, "text") . " ORDER BY id DESC LIMIT 1");
        
        if ($ilDB->numRows($query) == 0) {
            return [
                "intime" => - 1,
                "outtime" => 0
            ];
        } else {
            return $ilDB->fetchAssoc($query);
        }
    }

    /**
     * Add the view from this video from this user to DB.
     * If the view is an update from last view, update the DB.
     *
     * @param int $user_id            
     * @param string $episode_id            
     * @param array $view            
     * @access private
     */
    private static function addView($user_id, $episode_id, $view)
    {
        global $ilDB;
        
        if (array_key_exists("id", $view)) {
            $sql = "UPDATE " . self::DATATABLE . " SET intime = " . $ilDB->quote($view["intime"], "integer") . ", outtime = " . $ilDB->quote($view["outtime"], "integer") . " WHERE id = " . $ilDB->quote($view["id"], "integer");
        } else {
            $sql = "INSERT INTO " . self::DATATABLE . " (user_id, episode_id, intime, outtime) VALUES (" . $ilDB->quote($user_id, "integer") . ", " . $ilDB->quote($episode_id, "text") . ", " . $ilDB->quote($view["intime"], "integer") . ", " . $ilDB->quote($view["outtime"], "integer") . ")";
        }
        $ilDB->manipulate($sql);
    }

    /**
     * Get the statistics form a video, includes view count for 10 sec Intervals of the video
     *
     * @param ilMatterhornEpisode $episode            
     * @return array
     */
    public static function getStatisticFromVideo($episode)
    {
        global $ilDB;
        // TODO rechte
        // TODO video informationen manifest aus lesen
        $dataarray = [];
        
        $query = $ilDB->query("SELECT user_id, intime, outtime FROM " . self::DATATABLE . " WHERE intime >= 0 AND episode_id LIKE " . $ilDB->quote($episode->getEpisodeId(), "text"));
        if ($ilDB->numRows($query) > 0) {
            $users = array();
            while ($row = $ilDB->fetchAssoc($query)) {
                $user_id = $row['user_id'];
                
                $users[$user_id][] = $row;
            }
            $data = array();
            $useruniqueviewdata = array();
            foreach ($users as $user_id => $views) {
                $userviewdata = array();
                foreach ($views as $view) {
                    for ($time = (int) ($view['intime'] / 10); $time < (int) ($view['outtime'] / 10); $time ++) {
                        if (! array_key_exists($time, $userviewdata)) {
                            $userviewdata[$time] = 1;
                        } else {
                            $userviewdata[$time] ++;
                        }
                    }
                }
                
                foreach ($userviewdata as $time => $views) {
                    if (! array_key_exists($time, $data)) {
                        $data[$time] = $views;
                    } else {
                        $data[$time] += $views;
                    }
                    
                    if (! array_key_exists($time, $useruniqueviewdata)) {
                        $useruniqueviewdata[$time] = 1;
                    } else {
                        $useruniqueviewdata[$time] ++;
                    }
                }
            }
            
            $dataarray['views'] = $data;
            $dataarray['unique_views'] = $useruniqueviewdata;
        }
        return $dataarray;
    }

    /**
     * Get the Footprints form a video, user
     *
     * @param ilMatterhornEpisode $episode            
     * @param int $user_id            
     * @return array
     */
    public static function getFootprints($episode, $user_id)
    {
        global $ilDB;
        $array = array();
        
        $query = $ilDB->query("SELECT intime, outtime FROM " . self::DATATABLE . " WHERE intime >= 0 AND episode_id LIKE " . $ilDB->quote($episode->getEpisodeId(), "text") . " AND user_id = " . $ilDB->quote($user_id, "integer"));
        if ($ilDB->numRows($query) > 0) {
            $userviewdata = array();
            while ($view = $ilDB->fetchAssoc($query)) {
                for ($time = $view['intime']; $time < $view['outtime']; $time ++) {
                    if (! array_key_exists($time, $userviewdata)) {
                        $userviewdata[$time] = 1;
                    } else {
                        $userviewdata[$time] ++;
                    }
                }
            }
            
            ksort($userviewdata);
            $userviewdata[max(array_keys($userviewdata)) + 1] = 0;
            
            $last = - 1;
            $footprint = array();
            foreach ($userviewdata as $i => $current) {
                if ($last !== $current) {
                    $footprint[] = [
                        'position' => $i,
                        'views' => $current
                    ];
                }
                
                $last = $current;
            }
            
            $array['footprint'] = $footprint;
            $array['total'] = count($footprint);
        }
        return $array;
    }

    /**
     * Get the Views for a video
     *
     * @param ilMatterhornEpisode $episode            
     * @return int
     */
    public static function getViews($episode)
    {
        global $ilDB;
        
        $query = $ilDB->query("SELECT user_id, intime, outtime FROM " . self::DATATABLE . " WHERE episode_id LIKE " . $ilDB->quote($episode->getEpisodeId(), "text") . " ORDER BY id ASC");
        
        $viewsCount = 0;
        
        if ($ilDB->numRows($query) > 0) {
            $users = array();
            while ($row = $ilDB->fetchAssoc($query)) {
                $user_id = $row['user_id'];
                
                $users[$user_id][] = $row;
            }
            
            foreach ($users as $user_id => $views) {
                $userviews = 1;
                $lastouttime = - 1;
                foreach ($views as $view) {
                    if ($view['intime'] < $lastouttime) {
                        $userviews ++;
                    }
                    $lastouttime = $view['outtime'];
                }
                $viewsCount += $userviews;
            }
        }
        
        return $viewsCount;
    }

    /**
     * Get the seconds of the last view from this video from this user
     *
     * @param ilMatterhornEpisode $episode            
     * @param int $user_id            
     * @return int seconds
     */
    public static function getLastSecondViewed($episode, $user_id)
    {
        return self::getLastView($user_id, $episode->getEpisodeId())['outtime'];
    }

    /**
     * Delete all Views for the specified episode_id
     *
     * @param ilMatterhornEpisode $episode            
     */
    public static function removeViews($episode)
    {
        global $ilDB;
        
        $ilDB->manipulate("DELETE FROM " . self::DATATABLE . " WHERE episode_id LIKE " . $ilDB->quote($episode->getEpisodeId(), "text"));
    }
}