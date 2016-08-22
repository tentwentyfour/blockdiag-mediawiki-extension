=============================
Blockdiag MediaWiki Extension
=============================

Requirements
============

- blockdiag_ (or seqdiag, actdiag, nwdiag)
- mediawiki >= 1.25.0

.. _blockdiag: http://blockdiag.com/en/

Install
=======

1. Clone this repo inside to the ``${MEDIAWIKI_ROOT}/extension`` directory ::

    $ git clone https://github.com/tentwentyfour/blockdiag-mediawiki-extension

2. Add these lines to LocalSettings.php ::

    wfLoadExtension('blockdiag-mediawiki-extension');
    $wgBlockdiagPath = '/usr/bin/';      // default is /usr/local/bin/


Example
=======

::

        <blockdiag>
        {
                A -> B -> C
                     B -> D -> E
        }
        </blockdiag>

If you want to use other *diag tools, specify a name before "{", like "seqdiag {".

::

       <blockdiag>
       seqdiag {
               A -> B;
                    B -> C;
       }
       </blockdiag>
