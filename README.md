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

![Blockdiag example](/contrib/example.png?raw=true "Blockdiag example")

If you want to use other *diag tools, simply specify their name before the leading opening brace: "{", e.g. "seqdiag {".

```
  <blockdiag>
  seqdiag {
    A -> B;
         B -> C;
  }
  </blockdiag>
```

Semantic Result Format
======================

Requirements
------------

- [Semantic Result Format](https://www.semantic-mediawiki.org/wiki/Semantic_Result_Formats)

Templates
---------
- Server Template
```
<noinclude>
This is the "Server" template.

Edit the page to see the template text.
</noinclude><onlyinclude>
{| class="wikitable" style="width: 30em; font-size: 90%; border: 1px solid #aaaaaa; background-color: #f9f9f9; color: black; margin-bottom: 0.5em; margin-left: 1em; padding: 0.2em; float: right; clear: right; text-align:left;"
! style="text-align: left; background-color:#ccccff;" colspan="2" |<big>{{PAGENAME}}</big>
|-
! Fully Qualified Domain Name:
| [[Has fqdn::{{{fqdn|}}}]]
|-
! Tunneled IPs:
| {{{tipv4|}}} {{{tipv6|}}}
|-
! Uses VPN tunnel:
| {{#arraymap:{{{tunnel|}}}|,|x|[[Uses VPN tunnel::x]]}}
|}
<includeonly>
{{#set:
Has tunnel IPv4={{{tipv4|}}}
}}
[[Category:Server]]</includeonly></onlyinclude>
```

- Tunnel Template
```
<noinclude>
This is the "tunnel" template.
It should be called in the following format:
<pre>
{{tunnel
|purpose=
|subnet=
|server=
|port=
|clients=
}}
</pre>
Edit the page to see the template text.
</noinclude><includeonly>{| class="wikitable"
! colspan="2" | {{PAGENAME}}
|-
! Purpose
|  [[Has purpose::{{{purpose|}}}]]
|-
! Subnet
|  [[Has subnet::{{{subnet|}}}]]
|-
! Server
|  [[Has server::{{{server|}}}]]
|-
! Port
|  [[Has VPN port::{{{port|}}}]]
|-
! Hosts connected through this tunnel
| {{#ask:[[Uses VPN tunnel::{{SUBJECTPAGENAME}}]]|format=list|?Has tunnel IPv4=}}
|}
[[Category:Tunnel]]
</includeonly>

```

Example query
-------------
```
{{#ask:[[Uses VPN tunnel::Some tunnel name here]|format=nwdiag|gateway=foo2|?Has tunnel IPv4|?Has fqdn}}
```

The `gateway` parameter is optional.
