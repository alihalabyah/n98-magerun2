<?php

namespace N98\Magento\Command\Installer\SubCommand;

use N98\Magento\Command\SubCommand\AbstractSubCommand;

class RewriteHtaccessFile extends AbstractSubCommand
{
    /**
     * @return bool
     */
    public function execute()
    {
        if ($this->input->getOption('useDefaultConfigParams') == null
            || $this->input->getOption('replaceHtaccessFile') != null
        ) {
            $this->getCommand()->getApplication()->setAutoExit(false);
            $dialog = $this->getCommand()->getHelper('dialog');

            $args = $this->config->getArray('installation_args');

            $replaceHtaccessFile = false;

            if ($this->getCommand()->parseBoolOption($this->input->getOption('replaceHtaccessFile'))) {
                $replaceHtaccessFile = true;
            } elseif ($dialog->askConfirmation(
                $this->output,
                '<question>Write BaseURL to .htaccess file?</question> <comment>[n]</comment>: ',
                false
            )
            ) {
                $replaceHtaccessFile = true;
            }

            if ($replaceHtaccessFile) {
                $this->replaceHtaccessFile($args['base_url']);
            }
        }
    }

    /**
     * @param string $baseUrl
     */
    protected function replaceHtaccessFile($baseUrl)
    {
        $htaccessFile = $this->config->getString('installationFolder')
                      . DIRECTORY_SEPARATOR
                      . 'pub'
                      . DIRECTORY_SEPARATOR
                      . '.htaccess';

        $this->_backupOriginalFile($htaccessFile);
        $this->_replaceContent($htaccessFile, $baseUrl);
    }

    protected function _backupOriginalFile($htaccesFile)
    {
        copy(
            $htaccesFile,
            $htaccesFile . '.dist'
        );
    }

    /**
     * @param string $htaccessFile
     * @param string $baseUrl
     */
    protected function _replaceContent($htaccessFile, $baseUrl)
    {
        $content = file_get_contents($htaccessFile);
        $content = str_replace('#RewriteBase /magento/', 'RewriteBase ' . parse_url($baseUrl, PHP_URL_PATH), $content);
        file_put_contents($htaccessFile, $content);
    }
}