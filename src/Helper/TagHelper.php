<?php

namespace Ola\Assets\Helper;

use Ola\Assets\Tags\AdvertiserIdTag;
use Ola\Assets\Tags\FileExtensionTag;
use Ola\Assets\Tags\HistoryIdTag;
use Ola\Assets\Tags\TagInterface;
use Ola\Assets\Tags\UserIdTag;

class TagHelper
{
    const TAG_IDENTITY = 'identity';
    const TAG_IDENTITY_ID = 'id';
    const TAG_IDENTITY_INVOICE = 'invoice';
    const TAG_PENDING = 'pending';
    const TAG_IO = 'io';
    const TAG_PICTURE = 'picture';

    /**
     * @param string $filepath
     * @return string[]|TagInterface[]|callable[]
     * @link https://regex101.com/r/QKCnkt/2 Alter and test RegExp: Publisher validation documents (id+invoice)
     * @link https://regex101.com/r/VWDjur/4 Alter and test RegExp: Advertiser IO Docs
     */
    public static function detectTagsByPath(string $filepath): array
    {
        $tags = [];
        if (preg_match(
            '%\bidentity/user-(?<uid>\d+)/(history/(?<hid>\d+)_)?(?<type>id|invoice)\.(?<format>jpg|pdf)%',
            $filepath,
            $matches
        )) {
            $tags[] = self::TAG_IDENTITY;
            $tags[] = new UserIdTag((int)$matches['uid']);
            if (isset($matches['hid']) && ($matches['hid'] != '')) {
                $tags[] = new HistoryIdTag((int)$matches['hid']);
            } elseif (isset($matches['uid'])) {
                $tags[] = self::TAG_PENDING;
            }
            /**
             * $matches['type'] is one of {@see TagHelper::TAG_IDENTITY_ID} and {@see TagHelper::TAG_IDENTITY_INVOICE}
             */
            $tags[] = $matches['type'];
        }
        if (preg_match(
            '%\bio/(?<aid>\d+)/(?<filename>[^/]+)$%',
            $filepath,
            $matches
        )) {
            $tags[] = self::TAG_IO;
            $tags[] = new AdvertiserIdTag((int)$matches['aid']);
        }
        if (preg_match(
            '%\bpublic-files-users/(?<uid>\d+).(?<ext>\w+)$%',
            $filepath,
            $matches
        )) {
            $tags[] = self::TAG_PICTURE;
            $tags[] = new UserIdTag((int)$matches['uid']);
            $tags[] = new FileExtensionTag((string)$matches['ext']);
        }
        return $tags;
    }

    /**
     * @param string[]|TagInterface[]|callable[] $tags
     * @return string[]
     */
    public static function resolveTags(array $tags): array
    {
        return array_map(function ($tag) {
            return (string)(is_callable($tag) ? call_user_func($tag) : $tag);
        }, $tags);
    }
}
