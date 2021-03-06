<?php

namespace PieCrust\Interop\Importers;

use PieCrust\IPieCrust;
use PieCrust\PieCrustDefaults;
use PieCrust\PieCrustException;


/**
 * Base class for most importers.
 */
abstract class ImporterBase implements IImporter
{
    protected $name;
    protected $description;

    protected $logger;
    protected $pieCrust;
    protected $connection;

    /**
     * Builds a new instance of ImporterBase.
     */
    protected function __construct($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Gets the name of this importer.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the description of this importer.
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * Imports the website.
     */
    public function import(IPieCrust $pieCrust, $connection, $logger)
    {
        if ($logger == null)
            throw new PieCrustException("No logger was given for this importer.");
        $this->logger = $logger;

        $this->pieCrust = $pieCrust;
        $this->connection = $connection;

        $didClose = false;
        $this->open($connection);
        try
        {
            $this->setupConfig($this->pieCrust->getRootDir() . PieCrustDefaults::CONFIG_PATH);
            $this->importPages($this->pieCrust->getPagesDir());
            $this->importTemplates($this->pieCrust->getTemplatesDirs());
            $this->importPosts($this->pieCrust->getPostsDir(), $this->pieCrust->getConfig()->getValue('site/posts_fs'));
            $this->importStatic($this->pieCrust->getRootDir());

            $didClose = true;
            $this->close();
        }
        catch (Exception $e)
        {
            $this->logger->error($e->getMessage());
            if (!$didClose)
                $this->close();
        }
    }

    // Abstract functions {{{

    protected abstract function open($connection);
    protected abstract function importPages($pagesDir);
    protected abstract function importTemplates($templatesDirs);
    protected abstract function importPosts($postsDir, $mode);
    protected abstract function importStatic($rootDir);
    protected abstract function close();
    
    // }}}

    // Extension functions {{{

    protected function setupConfig($configPath)
    {
    }

    // }}}

    // Utility functions {{{
    
    protected function createPage($pagesDir, $name, $timestamp, $metadata, $content)
    {
        // Get a clean name.
        $name = $this->getCleanSlug($name);

        // Come up with the filename.
        $filename = $pagesDir . $name . '.html';

        // Build the config data that goes in the header.
        $configData = $metadata;
        $configData['date'] = date('Y-m-d', $timestamp);
        $configData['time'] = date('H:i:s', $timestamp);

        // Write it!
        $this->writePageFile($configData, $content, $filename);
    }

    protected function createPost($postsDir, $mode, $name, $timestamp, $metadata, $content)
    {
        // Get a clean name.
        $name = $this->getCleanSlug($name);

        // Come up with the filename.
        if ($mode == 'hierarchy')
        {
            $filename = $postsDir . date('Y', $timestamp) . DIRECTORY_SEPARATOR 
                . date('m', $timestamp) . DIRECTORY_SEPARATOR
                . date('d', $timestamp) . '_' . $name . '.html';
        }
        else if ($mode == 'shallow')
        {
            $filename = $postsDir . date('Y', $timestamp) . DIRECTORY_SEPARATOR
                . date('m-d', $timestamp) . '_' . $name . '.html';
        }
        else
        {
            $filename = $postsDir . date('Y-m-d', $timestamp) . '_' . $name . '.html';
        }

        // Build the config data that goes in the header.
        $configData = $metadata;
        if (!isset($configData['time']))
            $configData['time'] = date('H:i:s', $timestamp);

        // Write it!
        $this->writePageFile($configData, $content, $filename);
    }

    protected function writePageFile($configData, $content, $filename)
    {
        // Get the YAML string for the config data.
        $yaml = new \sfYamlDumper();
        $header = $yaml->dump($configData, 3);

        // Write the post's contents.
        echo " > " . pathinfo($filename, PATHINFO_FILENAME) . "\n";
        if (!is_dir(dirname($filename)))
            mkdir(dirname($filename), 0777, true);
        $f = fopen($filename, 'w');
        fwrite($f, "---\n");
        fwrite($f, $header);
        fwrite($f, "---\n");
        fwrite($f, $content);
        fclose($f);

    }

    // }}}
}
