<?php

namespace JCli\Joomla;

class ManiferstReader
{

    public static function discoverManifest($name, $path)
    {

        $temp = explode('_', $name);
        array_shift($temp);
        $temp = implode('_', $temp);

        $names = [
            $name . '.xml',
            $temp . '.xml'
        ];

        $path = realpath($path);
        foreach ($names as $name) {
            $manifest = realpath($path . '/' . $name);

            if ($manifest) return $manifest;
        }

        return false;
    }

    public static function discoverExtension($name, $base_path)
    {
        $paths = [
            'administrator/components',
            'administrator/modules',
            'components',
            'modules',
        ];

        $base_path = realpath($base_path);
        foreach ($paths as $path) {
            $path = realpath($base_path . '/' . $path . '/' . $name);

            if ($path) return $path;
        }

        return false;
    }

    public static function parse($path, $base_path)
    {
        if (!is_file($path)) throw new \InvalidArgumentException('Path to manifest XML is incorrect');
        if (!is_dir($base_path)) throw new \InvalidArgumentException('Base path is incorrect');

        $path      = realpath($path);
        $base_path = realpath($base_path);

        $xml = @  simplexml_load_file($path);
        if (!$xml) throw new \InvalidArgumentException('Manifest XML ' . $path . ' is invalid');

        $result         = [];
        $result['info'] = self::extractInformation($xml);

        $manifest_path = str_replace($base_path, '', dirname($path));
        $manifest_path = ltrim($manifest_path, '/');
        $manifest_path = explode('/', $manifest_path);

        if ($manifest_path[0] == 'administrator') {
            $administrator_path = implode('/', $manifest_path);

            array_shift($manifest_path);
            $site_path = implode('/', $manifest_path);
        } else {
            $site_path = implode('/', $manifest_path);

            array_unshift($manifest_path, 'administrator');
            $administrator_path = implode('/', $manifest_path);
        }

        $result['paths'] = [
            'site'          => $base_path . '/' . $site_path,
            'administrator' => $base_path . '/' . $administrator_path
        ];

        $files            = self::extractFiles($xml, $base_path, $site_path, $administrator_path);
        $basename         = basename($path);
        $files[$basename] = [$path, $basename];
        $result['files']  = array_filter($files, function ($item) {
            if (strpos($item[0], '.DS_Store') !== false) return false;
            if (strpos($item[0], 'Thumbs.db') !== false) return false;

            return true;
        });

        return $result;
    }

    protected static function extractInformation(\SimpleXMLElement $xml)
    {

        $result = [
            'extension'    => [
                'type'    => self::getAttribute($xml, 'type'),
                'version' => self::getAttribute($xml, 'version'),
                'method'  => self::getAttribute($xml, 'method'),
            ],
            'name'         => self::getTagStringValue($xml, 'name'),
            'version'      => self::getTagStringValue($xml, 'version'),
            'author'       => self::getTagStringValue($xml, 'author'),
            'authorEmail'  => self::getTagStringValue($xml, 'authorEmail'),
            'authorUrl'    => self::getTagStringValue($xml, 'authorUrl'),
            'description'  => self::getTagStringValue($xml, 'description'),
            'creationDate' => self::getTagStringValue($xml, 'creationDate'),
            'copyright'    => self::getTagStringValue($xml, 'copyright'),
            'license'      => self::getTagStringValue($xml, 'license'),
        ];

        return $result;
    }

    protected static function extractFiles(\SimpleXMLElement $xml, $base_path, $site_path, $administrator_path)
    {

        $result = [];

        foreach ($xml->files as $node) {
            $folder = self::getAttribute($node, 'folder');
            $files  = self::getFiles($node, $site_path, $folder);

            foreach ($files as $file) $result[] = $file;
        }

        if ($xml->administration->files) {
            foreach ($xml->administration->files as $node) {
                $folder = self::getAttribute($node, 'folder');
                $files  = self::getFiles($node, $administrator_path, $folder);

                foreach ($files as $file) $result[] = $file;
            }
        }

        if ($xml->languages) {
            foreach ($xml->languages as $node) {
                $folder = self::getAttribute($node, 'folder');
                $files  = self::getLanguageFiles($node, $base_path, $folder);

                foreach ($files as $file) $result[] = $file;

            }
        }

        if ($xml->administration->languages) {
            foreach ($xml->administration->languages as $node) {
                $folder = self::getAttribute($node, 'folder');
                $files  = self::getLanguageFiles($node, $base_path . '/administrator', $folder);

                foreach ($files as $file) $result[] = $file;

            }
        }

        ksort($result);

        return $result;
    }

    protected function getLanguageFiles(\SimpleXMLElement $xml, $base_path, $folder)
    {
        $result = [];

        $folder = str_replace("\\", '/', $folder);
        $folder = trim($folder, '/');
        if (!empty($folder)) $folder .= '/';

        foreach ($xml->language as $item) {
            $tag  = self::getAttribute($item, 'tag');
            $item = (string)$item;

            $temp   = str_replace("\\", "/", $item);
            $temp   = explode('/', $temp);
            $last   = array_pop($temp);
            $temp[] = $tag;
            $temp[] = $last;
            $temp   = implode('/', $temp);
            $temp   = $base_path . '/' . $temp;

            if (!is_file($temp)) throw new \RuntimeException($temp . ' is not a valid file');

            if ($item) $result[$temp] = [
                $temp,
                $folder . $item
            ];
        }

        return $result;
    }

    protected function getFiles(\SimpleXMLElement $xml, $base_path, $folder)
    {
        $result = [];

        foreach ($xml->filename as $item) {
            $item = (string)$item;
            $item = str_replace("\\", "/", $item);
            $item = $base_path . '/' . $item;

            if (!is_file($item)) throw new \RuntimeException($item . ' is not a valid file');

            $item          = realpath($item);
            $result[$item] = $item;
        }

        foreach ($xml->folder as $item) {
            $item = (string)$item;
            $item = str_replace("\\", "/", $item);
            $item = $base_path . '/' . $item;

            if (!is_dir($item)) throw new \RuntimeException($item . ' is not a valid directory');
            $item = realpath($item);

            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($item, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
            );

            foreach ($iter as $value) {
                if ($value->isFile()) {
                    $value          = $value->getRealPath();
                    $result[$value] = $value;
                }
            }
        }

        if (!empty($result)) {
            $folder    = str_replace("\\", '/', $folder);
            $folder    = trim($folder, '/');
            $base_path = realpath($base_path);

            $result = array_map(function ($item) use ($base_path, $folder) {

                $path_archive = str_replace($base_path, '', $item);
                $path_archive = str_replace("\\", '/', $path_archive);
                $path_archive = ltrim($path_archive, '/');

                if (!empty($folder)) $path_archive = $folder . '/' . $path_archive;

                return [
                    $item,
                    $path_archive
                ];
            }, $result);
        }

        return $result;
    }

    protected function getAttribute(\SimpleXMLElement $xml, $attribute)
    {
        return isset($xml[$attribute]) ? trim(strval($xml[$attribute])) : '';
    }

    protected function getTagStringValue(\SimpleXMLElement $xml, $tag)
    {
        return isset($xml->$tag) ? trim(strval($xml->$tag)) : '';
    }


}

