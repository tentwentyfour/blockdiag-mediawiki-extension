Blockdiag MediaWiki Extension
=============================

Since version 1.1.0, this extension provides two services:

1. a [ParserHook](#Using_the_ParserHook) to be used when manually specifying blockdiag DSL
1. a [ResultPrinter](#Using_the_Semantic_Result_Printer) for [SemanticMediaWiki](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki). (_Currently this only supports the `nwdiag` format._)


Requirements
------------

- [blockdiag](http://blockdiag.com/en/) (and/or seqdiag, actdiag, nwdiag)
- mediawiki >= 1.25.0


Installation
------------

1. Simply run the following command inside your mediawiki doc-root:
```
    $ composer require tentwentyfour/blockdiag-mediawiki-extension
```

2. Then add this line to the end of your LocalSettings.php :
```
    wfLoadExtension('BlockdiagMediawiki');
```

3. (Optional) If you installed your blockdiag package somewhere else than the default, you may tell the plugin where to find the binaries:
```
    $wgBlockdiagPath = '/usr/bin/';      // default is /usr/local/bin/
```

Using the ParserHook
====================

```
<blockdiag>
{
    A -> B -> C
         B -> D -> E
}
</blockdiag>
```

![Blockdiag example](/contrib/example.png?raw=true "Blockdiag example")

If you want to use other *diag tools, simply specify their name before the leading opening brace: "{", e.g. `seqdiag {` or `nwdiag {`.

```
<blockdiag>
seqdiag {
    A -> B;
         B -> C;
}
</blockdiag>
```

![Seqdiag example](/contrib/seqdiag.png?raw=true "Seqdiag example")

```
<blockdiag>
nwdiag {
inet [shape = cloud];
inet -- router;
network dmz {
      router;
      address = "210.x.x.x/24"
      group web {
          web01 [address = "210.x.x.1, 10.42.100.1"];
          web02 [address = "210.x.x.2"];
      }
  }
  network internal {
      address = "172.x.x.x/24";

      web01 [address = "172.x.x.1"];
      web02 [address = "172.x.x.2"];
      db01;
      db02;
  }
}
</blockdiag>
```

![Nwdiag example](/contrib/nwdiag.png?raw=true "Nwdiag example")


Using the Semantic Result Printer
=================================

If you have Semantic Mediawiki installed, you can use this extension as a result printer as well, generating diagrams directly from your semantic queries.


Requirements
------------

- [Semantic Mediawiki](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki)
- [Semantic Result Format](https://www.semantic-mediawiki.org/wiki/Semantic_Result_Formats)

Templates
---------

The following templates are given as an example only and present the bare minimum of properties required for the result printer to give you sensible results.
Note that you can adjust the style and properties as you wish, as long as you make sure your `{{#ask:}}` query returns IP addresses and fully qualified domain names (fqdn) for the selected nodes.

We have omitted forms and property details. Semantic Forms will simplify the creation of your Server and Tunnel entries.


### Server Template

```
<onlyinclude>
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

### Tunnel Template

```
<includeonly>{| class="wikitable"
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
{{#ask:[[Uses VPN tunnel::Some tunnel name here]|format=nwdiag|domain=srv.domain.tld|gateway=baz|?Has tunnel IPv4=ipv4|?Has fqdn=fqdn}}
```

The `gateway` parameter is optional.
