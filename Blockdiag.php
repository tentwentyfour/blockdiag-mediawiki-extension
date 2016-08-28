<?php
/**
 * Blockdiag Extension for MediaWiki
 *
 * @since 1.1.0
 * @version 1.1.0
 *
 * @author Kazunori Kojima
 * @author David Raison <david@tentwentyfour.lu>
 * @author Gilles Magalhaes <gilles@tentwentyfour.lu>
 *
 **/

class Blockdiag
{
    /**
     * Registers both the parserhook and the semantic result printer
     *
     * @param  Parser &$parser Mediawiki Parser
     *
     * @return boolean         Returns true unless an error ocurred
     */
    public function parserInit(&$parser)
    {
        global $srfgFormats, $smwgResultFormats;

        $srfgFormats[] = 'nwdiag';
        $smwgResultFormats['nwdiag'] = 'NwdiagResultPrinter';

        $parser->setHook('blockdiag', 'Blockdiag::display');
        return true;
    }

    public function display($input, $args, $parser)
    {
        global $wgTmpDirectory;
        global $wgUploadDirectory;
        global $wgUploadPath;
        global $wgBlockdiagPath;

        $wgBlockdiagDirectory = "$wgUploadDirectory/blockdiag";
        $wgBlockdiagUrl = "$wgUploadPath/blockdiag";

        $newBlockdiag = new BlockdiagGenerator(
            $wgBlockdiagDirectory,
            $wgBlockdiagUrl,
            $wgTmpDirectory,
            $input,
            $wgBlockdiagPath
        );
        $html = $newBlockdiag->showImage();

        return $html;
    }
}
