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
        $GLOBALS['srfgFormats'][] = 'nwdiag';
        $GLOBALS['smwgResultFormats']['nwdiag'] = 'NwdiagResultPrinter';

        $parser->setHook('blockdiag', 'Blockdiag::display');
        return true;
    }

    public function display($input)
    {
        $wgBlockdiagDirectory = $GLOBALS['wgUploadDirectory'] . "/blockdiag";
        $wgBlockdiagUrl = $GLOBALS['wgUploadPath'] . "/blockdiag";

        $newBlockdiag = new BlockdiagGenerator(
            $wgBlockdiagDirectory,
            $wgBlockdiagUrl,
            $GLOBALS['wgTmpDirectory'],
            $input,
            $GLOBALS['wgBlockdiagPath']
        );
        $html = $newBlockdiag->showImage();

        return $html;
    }
}
