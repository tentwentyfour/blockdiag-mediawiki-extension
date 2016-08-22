Blockdiag MediaWiki Extension
=============================

Requirements
------------

- [blockdiag](http://blockdiag.com/en/) (or seqdiag, actdiag, nwdiag)
- mediawiki >= 1.25.0


Install
-------

1. Simply run the following command inside your mediawiki doc-root:
```
  $ composer require tentwentyfour/blockdiag-mediawiki-extension
```

2. Then add these lines to LocalSettings.php ::
```
    wfLoadExtension('BlockdiagMediawiki');
```

3. (Optional) If you installed your blockdiag package somewhere else than the default, you may tell the plugin where to find the binaries:
```
    $wgBlockdiagPath = '/usr/bin/';      // default is /usr/local/bin/
```

Example
=======

```
  <blockdiag>
  {
    A -> B -> C
         B -> D -> E
  }
  </blockdiag>
```

If you want to use other *diag tools, specify their name before the leading opening brace: "{", e.g. "seqdiag {".

```
  <blockdiag>
  seqdiag {
    A -> B;
         B -> C;
  }
  </blockdiag>
```
