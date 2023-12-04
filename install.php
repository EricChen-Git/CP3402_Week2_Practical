<?php

defined('_JEXEC') or die;

use Gantry\Framework\Gantry;
use Gantry\Framework\ThemeInstaller;
use Gantry5\Loader;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Class Jl_BestoInstallerScript
 */
class Jl_BestoInstallerScript
{
    /** @var string */
    public $requiredGantryVersion = '5.5';

    /**
     * @param string $type
     * @param object $parent
     * @return bool
     * @throws Exception
     */
    public function preflight($type, $parent)
    {
        if ($type === 'uninstall') {
            return true;
        }

        // Replace folders
        $layoutfolders = array(
            '/templates/'.$parent->name.'/html/com_content',
            '/templates/'.$parent->name.'/html/com_contact',
            '/templates/'.$parent->name.'/html/layouts/joomla/content',
            '/templates/'.$parent->name.'/html/layouts/joomla/pagination',
            '/templates/'.$parent->name.'/html/mod_articles_news',
            '/templates/'.$parent->name.'/html/mod_menu'
        );

        foreach ($layoutfolders as $lfolder)
        {
            if ($lfolderExists = Folder::exists(JPATH_ROOT . $lfolder))
            {
                Folder::delete(JPATH_ROOT . $lfolder);
            }
        }

        $manifest = $parent->getManifest();
        $name = Text::_($manifest->name);

        // Prevent installation if Gantry 5 isn't enabled or is too old for this template.
        try {
            if (!class_exists('Gantry5\Loader')) {
                throw new RuntimeException(sprintf('Please install Gantry 5 Framework before installing %s template!', $name));
            }

            Loader::setup();

            $gantry = Gantry::instance();

            if (!method_exists($gantry, 'isCompatible') || !$gantry->isCompatible($this->requiredGantryVersion)) {
                throw new \RuntimeException(sprintf('Please upgrade Gantry 5 Framework to v%s (or later) before installing %s template!', strtoupper($this->requiredGantryVersion), $name));
            }

        } catch (Exception $e) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::sprintf($e->getMessage()), 'error');

            return false;
        }

        return true;
    }

    /**
     * @param string $type
     * @param object $parent
     * @throws Exception
     */
    public function postflight($type, $parent)
    {
        if ($type === 'uninstall') {
            return true;
        }

        $installer = new ThemeInstaller($parent);
        $installer->initialize();

        // Clean folders
        $folders = array(
            '/templates/'.$parent->name.'/html/com_finder',
            '/templates/'.$parent->name.'/html/com_users',
            '/templates/'.$parent->name.'/html/mod_articles_latest',
            '/templates/'.$parent->name.'/html/mod_breadcrumbs',
            '/templates/'.$parent->name.'/html/mod_login'
        );

        foreach ($folders as $folder)
        {
            if ($folderExists = Folder::exists(JPATH_ROOT . $folder))
            {
                Folder::delete(JPATH_ROOT . $folder);
            }
        }
        
        // Install sample data on first install.
        if (in_array($type, array('install', 'discover_install'))) {
            try {
                $installer->installDefaults();

                echo $installer->render('install.html.twig');

            } catch (Exception $e) {
                $app = Factory::getApplication();
                $app->enqueueMessage(Text::sprintf($e->getMessage()), 'error');
            }
        } else {
            echo $installer->render('update.html.twig');
        }

        $installer->finalize();

        return true;
    }

    /**
     * Called by TemplateInstaller to customize post-installation.
     *
     * @param ThemeInstaller $installer
     */
    public function installDefaults(ThemeInstaller $installer)
    {
        // Create default outlines etc.
        $installer->createDefaults();
    }

    /**
     * Called by TemplateInstaller to customize sample data creation.
     *
     * @param ThemeInstaller $installer
     */
    public function installSampleData(ThemeInstaller $installer)
    {
        // Create sample data.
        $installer->createSampleData();
    }
}
