<?php
/**
 * Blockdiag Extension for MediaWiki
 *
 * @since 1.1.0
 * @version 1.1.0
 *
 * @author Gilles Magalhaes <gilles@tentwentyfour.lu>
 *
 **/

class NwdiagResultPrinter extends SMWResultPrinter
{
    public function getName()
    {
        return wfMessage('nwdiag')->text();
    }

    protected function getResultText(SMWQueryResult $result, $outputMode)
    {
        $data = $this->getResultData($result);

        if (empty($data)) {
            return $result->addErrors(
                [
                    wfMessage('srf-no-results')->inContentLanguage()->text(),
                ]
            );
        } else {
            return $this->getFormatOutput($data);
        }
    }

    /**
     * Extracts the data we need to build a networkdiag DSL from the
     * semantic result
     *
     * @param  SMWQueryResult $result Result of the query
     *
     * @return array                  Array of data to be fed to generateDiagCode()
     */
    protected function getResultData(SMWQueryResult $result)
    {
        $data = array();

        while ($rows = $result->getNext()) {
            foreach ($rows as $field) {
                $propertyLabel = $field->getPrintRequest()->getLabel();

                $server_name = $field->getResultSubject()->getTitle();
                $server_name = strtolower($server_name->getFullText());

                while (($dataValue = $field->getNextDataValue()) !== false) {
                    if ($propertyLabel === "ipv4") {
                        $ip_address = $dataValue->getWikiValue();
                        $data[$server_name]["ip_address"] = $ip_address;
                    } elseif ($propertyLabel === "fqdn") {
                        $fqdn = strtolower($dataValue->getWikiValue());

                        if (isset($this->params['domain'])) {
                            $fqdn = str_replace($this->params['domain'], '', $fqdn);
                        }

                        $domain_parts = explode('.', $fqdn);
                        $hostname = $domain_parts[0];
                        $group ='';

                        if (count($domain_parts) >= 1) {
                            $group = $domain_parts[1];
                            $node_host = sprintf('%s.%s', $hostname, $group);
                        }

                        $data[$server_name]["fqdn"] = $fqdn;
                        $data[$server_name]["node_host"] = !empty($group) ? $node_host : $hostname;
                        $data[$server_name]["group"] = $group;
                    }
                }

                // If a row does not have a value for the FQDN property label, it will not be returned
                // by $field->getNextDataValue()
                if (!isset($data[$server_name]["fqdn"])) {
                    $data[$server_name]["fqdn"] = $server_name;
                    $data[$server_name]["node_host"] = $server_name;
                    $data[$server_name]["group"] = $server_name;
                }
            }
        }

        return $data;
    }

    /**
     * @see SMWResultPrinter::getParamDefinitions
     *
     * @since 1.1.0
     *
     * @param $definitions array of IParamDefinition
     *
     * @return array of IParamDefinition|array
     */
    public function getParamDefinitions(array $definitions)
    {
        $params = parent::getParamDefinitions($definitions);

        $params['gateway'] = [
           'message'=>'The node that will be used as a gateway',
           'default'=>'',
        ];

        $params['domain'] = [
           'message'=>'Specify which part of the fqdn is the domain name and should be stripped',
           'default'=>'localhost.lan',
        ];

        return $params;
    }

    /**
     * Prepare data for the output
     *
     * @since 1.1.2
     *
     * @param array $data
     * @param array $options
     *
     * @return string
     */
    protected function getFormatOutput($data)
    {
        $this->isHTML = true;

        $wgBlockdiagDirectory = $GLOBALS['wgUploadDirectory'] . "/blockdiag";
        $wgBlockdiagUrl = $GLOBALS['wgUploadPath'] . "/blockdiag";

        $nwdiagCode = $this->generateDiagCode($data, $this->params['gateway']);

        $newBlockdiag = new BlockdiagGenerator(
            $wgBlockdiagDirectory,
            $wgBlockdiagUrl,
            $GLOBALS['wgTmpDirectory'],
            $nwdiagCode,
            $GLOBALS['wgBlockdiagPath']
        );

        return $newBlockdiag->showImage();
    }

    /**
     * Generate the blockdiag DSL to be rendered by our BlockdiagGenerator
     *
     * @param  array  $nodes   Array of nodes to be added to the diagram
     * @param  string $gateway Node that will be used as a gateway
     *
     * @return string          String to be rendered
     */
    private function generateDiagCode($nodes, $gateway)
    {
        $diagCode ='nwdiag {'. PHP_EOL;

        // draw gateway
        $match = $this->findGateway($nodes, $gateway);
        if ($match != "") {
            $diagCode .= "\t" . $match[1] . " [shape = ellipse];" . PHP_EOL;
            $diagCode .= "\t" . $match[1] . " -- " . implode(".", $match) . "\n\n";
        }

        // draw network
        $diagCode .= "\tnetwork vpn {\n";
        foreach ($nodes as $node) {
            $ip = $node['ip_address'];
            $node_host = $node['node_host'];
            $diagCode .= "\t\t\"$node_host\" [address=\"$ip\"];" . PHP_EOL;
        }
        $diagCode .= "\t}" . PHP_EOL;

        // draw groups
        $groups = $this->findNodeGroups($nodes);
        foreach (array_keys($groups) as $group) {
            // don't create a group if there is only one node inside it.
            if ($group ==''|| $groups[$group] == 1) {
                continue;
            }

            $diagCode .= "\tgroup $group {\n";
            $diagCode .= "\t\tcolor = \"" . $this->randomColor() . "\";\n";
            $diagCode .= "\n";

            foreach ($nodes as $node) {
                if ($node['group'] === $group) {
                    $diagCode .= "\t\t" . $node['node_host'] . ";" . PHP_EOL;
                }
            }
            $diagCode .= "\t}\n\n";
        }
        $diagCode .= "}\n";

        return $diagCode;
    }

    /**
     * Walk through the $nodes array to find all groups with
     * @param  [type] $nodes [description]
     * @return [type]        [description]
     */
    private function findNodeGroups($nodes)
    {
        $groups = array();

        foreach ($nodes as $node) {
            $nodeGroup = $node['group'];
            if (!array_key_exists($nodeGroup, $groups)) {
                $groups[$nodeGroup] = 1;
            } else {
                $groups[$nodeGroup]++;
            }
        }

        return $groups;
    }

    private function findGateway($nodes, $gateway)
    {
        $match = null;
        foreach ($nodes as $node) {
            $node_host = $node['node_host'];
            if (strpos($node_host, $gateway) !== false) {
                $match = explode(".", $node_host);
                break;
            }
        }
        return $match;
    }

    /**
     * Generate a random, but light color
     *
     * @return string Hexadecimal rgb color definition
     */
    public function randomColor()
    {
        $color ='#';
        for ($i = 0; $i <= 2; $i++) {
            $color .= dechex(mt_rand(0xAA, 0xFF));
        }
        return $color;
    }
}
