<?php
namespace TIK_NFL\ilias_oc_plugin\opencast;

/**
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastUtil
{

    public static function getSearchPreviewURL(array $attachments)
    {
        $previewurl = null;
        foreach ($attachments as $attachment) {
            switch ($attachment->flavor) {
                case 'presentation/search+preview':
                    // prefer presentation/search+preview over presenter/search+preview
                    return $attachment->url;
                case 'presenter/search+preview':
                    $previewurl = $attachment->url;
                    // continue searching for a presentation/search+preview
                    break;
            }
        }
        return $previewurl;
    }

    public static function getTrackDownloadURL(array $tracks)
    {
        $downloadurl = null;
        foreach ($tracks as $track) {
            if ('composite/delivery' == $track->flavor && 'video/mp4' == $track->mediatype) {
                return $track->url;
            } else if ('presentation/delivery' == $track->flavor && 'video/mp4' == $track->mediatype) {
                $downloadurl = $track->url;
            }
        }
        return $downloadurl;
    }
}