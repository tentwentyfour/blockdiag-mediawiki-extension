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

class BlockdiagGenerator
{
    private $path_array = array(
        'blockdiag',
        'seqdiag',
        'actdiag',
        'nwdiag',
        'rackdiag',
        'packetdiag'
    );
    private $imgType = 'png';
    private $hash;
    private $source;
    private $tmpDir;
    private $blockdiagDir;
    private $blockdiagUrl;


    public function __construct($blockdiagDir, $blockdiagUrl, $tmpDir, $source, $binPath = null)
    {
        $this->blockdiagDir  = $blockdiagDir;
        $this->blockdiagUrl = $blockdiagUrl;
        $this->tmpDir        = $tmpDir;
        $this->hash          = md5($source);
        $this->source        = $source;
        $path = ($binPath !== null) ? $binPath : '/usr/local/bin/';
        $this->path_array = array_flip($this->path_array);
        array_walk($this->path_array, function (&$diag, $binary) use ($path) {
            $diag = rtrim($path, '/') . DIRECTORY_SEPARATOR . $binary;
        });
    }

    public function showImage()
    {
        if (file_exists($this->getImagePath())) {
            $html = $this->mkImageTag();
        } else {
            $html = $this->generate();
        }

        return $html;
    }

    private function generate()
    {
        if (preg_match('/^\s*(\w+)\s*{/', $this->source, $matches)) {
            $diagram_type = $matches[1];
        } else {
            $diagram_type = 'blockdiag'; // blockdiag for default
        }

        $diagprog_path = $this->path_array[$diagram_type];

        if (!is_file($diagprog_path)) {
            return $this->error("$diagram_type is not found at the specified place.");
        }

        // temporary directory check
        if (!file_exists($this->tmpDir)) {
            if (!wfMkdirParents($this->tmpDir)) {
                return $this->error('temporary directory is not found.');
            }
        } elseif (!is_dir($this->tmpDir)) {
            return $this->error('temporary directory is not directory');
        } elseif (!is_writable($this->tmpDir)) {
            return $this->error('temporary directory is not writable');
        }

        // create temporary file
        $dstTmpName = tempnam($this->tmpDir, 'blockdiag');
        $srcTmpName = tempnam($this->tmpDir, 'blockdiag');

        // write blockdiag source
        $fp = fopen($srcTmpName, 'w');
        fwrite($fp, $this->source);
        fclose($fp);

        // generate blockdiag image
        $cmd = $diagprog_path . ' -T ' .
            escapeshellarg($this->imgType) . ' -o ' .
            escapeshellarg($dstTmpName) . ' ' .
            escapeshellarg($srcTmpName);

        $res = `$cmd`;

        if (filesize($dstTmpName) == 0) {
            return $this->error('unknown error.');
        }

        // move to image directory
        $hashpath = $this->getHashPath();
        if (!file_exists($hashpath)) {
            if (!@wfMkdirParents($hashpath, 0755)) {
                return $this->error('can not make blockdiag image directory', $this->blockdiagDir);
            }
        } elseif (!is_dir($hashpath)) {
            return $this->error('blockdiag image directory is already exists. but not directory');
        } elseif (!is_writable($hashpath)) {
            return $this->error('blockdiag image directory is not writable');
        }

        if (!rename("$dstTmpName", "$hashpath/{$this->hash}.png")) {
            return $this->error('can not rename blockdiag image');
        }

        return $this->mkImageTag();
    }

    private function mkImageTag()
    {
        $url = $this->getImageUrl();

        return Html::rawElement(
            'div',
            array(
                'style' => 'overflow-x: scroll'
            ),
            Html::element(
                'img',
                array(
                    'class' => 'blockdiag',
                    'src' => $url
                )
            )
        );
    }

    private function getImageUrl()
    {
        return "{$this->blockdiagUrl}/{$this->getHashSubPath()}/{$this->hash}.png";
    }

    private function getImagePath()
    {
        return "{$this->blockdiagDir}/{$this->getHashSubPath()}/{$this->hash}.png";
    }

    private function getHashPath()
    {
        return "{$this->blockdiagDir}/{$this->getHashSubPath()}";
    }

    private function getHashSubPath()
    {
        return substr($this->hash, 0, 1)
            .'/'. substr($this->hash, 1, 1)
            .'/'. substr($this->hash, 2, 1);
    }

    private function error($msg, $append = '')
    {
        $mf     = 'blockdiag';
        $errmsg = htmlspecialchars($msg . ' ' . $append);
        return "<strong class='error'>$mf ($errmsg)</strong>\n";
    }
}
