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
        return wfMessage( 'nwdiag' )->text();
    }

    protected function getResultText(SMWQueryResult $result, $outputMode)
    {
        global $wgTmpDirectory;
        global $wgUploadDirectory;
        global $wgUploadPath;
        global $wgBlockdiagPath;

        $data = $this->getResultData($result);

        if (empty($data)) {
            return $result->addErrors(
                [
                    wfMessage( 'srf-no-results' )->inContentLanguage()->text(),
                ]
            );
        }

        $wgBlockdiagDirectory = "$wgUploadDirectory/blockdiag";
        $wgBlockdiagUrl = "$wgUploadPath/blockdiag";

        $nwdiagCode = $this->generateDiagCode($data, $this->params['gateway']);

        $newBlockdiag = new BlockdiagGenerator(
            $wgBlockdiagDirectory,
            $wgBlockdiagUrl,
            $wgTmpDirectory,
            $nwdiagCode,
            $wgBlockdiagPath
        );

        $this->isHTML = true;

        return $newBlockdiag->showImage();
    }


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

                        if (!strlen($fqdn)) {
                            $data[$server_name]["fqdn"] = $server_name;
                            $data[$server_name]["node_host"] = $server_name;
                            $data[$server_name]["group"] = $server_name;
                            continue;
                        }

                        if (isset($this->params['domain'])) {
                            $fqdn = str_replace($this->params['domain'], '', $fqdn);
                        }

                        $domain_parts = explode('.', $fqdn);
                        $group = '';

                        $hostname = $domain[0];
                        if (count($domain_parts) >= 1) {
                            $group = $domain_parts[1];
                        }

                        $data[$server_name]["fqdn"] = $fqdn;
                        $data[$server_name]["node_host"] = implode(".", array_slice($domain_parts, 0, 2));
                        $data[$server_name]["group"] = $group;
                    }
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

        $params['gateway'] = array(
            'message' => 'The node that will be used as a gateway',
            'default' => '',
        );

        return $params;
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
        $diagCode = 'nwdiag {' . PHP_EOL;

        // draw gateway
        $match = $this->findGateway( $nodes, $gateway );
        if ($match != "") {
            $diagCode .= "\t" . $match[1] . " [shape = ellipse];" . PHP_EOL;
            $diagCode .= "\t" . $match[1] . " -- " . implode(".", $match) . "\n\n";
        }

        // draw network
        $diagCode .= "\tnetwork vpn {\n";
        foreach ($nodes as $node) {
            $ip = $node['ip_address'];
            $partial_fqdn = $node['partial_fqdn'];
            $diagCode .= "\t\t$partial_fqdn [address=\"$ip\"];" . PHP_EOL;
        }
        $diagCode .= "\t}" . PHP_EOL;

        // draw groups
        $groups = $this->findNodeGroups( $nodes );
        foreach (array_keys($groups) as $group) {
            // don't create a group if there is only one node inside it.
            if ($group == '' || $groups[$group] == 1) {
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

    private function findGateway( $nodes, $gateway )
    {
        $match = null;
        foreach ($nodes as $node) {
            $partial_fqdn = $node['partial_fqdn'];
            if (strpos($partial_fqdn, $gateway) !== false) {
                $match = explode(".", $partial_fqdn);
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
        $color = '#';
        for ($i = 0; $i <= 2; $i++) {
            $color .= dechex(mt_rand(0xAA, 0xFF));
        }
        return $color;
    }
}
