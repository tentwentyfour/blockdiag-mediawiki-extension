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

1. Copy blockdiag.php to ${MEDIAWIKI_ROOT}/extension/ ::

   $ sudo cp blockdiag.php ${MEDIAWIKI_ROOT}/extension/

2. Add line to LocalSettings.php ::

::

   require_once("$IP/extensions/blockdiag.php");
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
