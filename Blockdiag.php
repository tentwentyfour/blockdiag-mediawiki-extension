<?php
/**
 * Blockdiag Extension for MediaWiki
 *
 * @since 0.0.1
 * @version 1.0.1
 *
 **/
class Blockdiag {
    public function parserInit ( &$parser ) {
        global $srfgFormats, $smwgResultFormats;
        $srfgFormats[] = 'nwdiag';
        $smwgResultFormats['nwdiag'] = 'NwdiagResultPrinter';

        $parser->setHook( 'blockdiag', 'Blockdiag::display' );
        return true;
    }

    public function display( $input, $args, $parser ){
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
