<?php

class NwdiagResultPrinter extends SMWResultPrinter {
    public function getName() {
        return wfMessage( 'nwdiag' )->text();
    }

    protected function getResultText( SMWQueryResult $result, $outputMode ) {

        $data = $this->getResultData( $result );

        if ( $data === array() ) {
            return $result->addErrors( array( wfMessage( 'srf-no-results' )->inContentLanguage()->text()));
        }

        global $wgTmpDirectory;
		global $wgUploadDirectory;
		global $wgUploadPath;
		global $wgBlockdiagPath;

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


    protected function getResultData( SMWQueryResult $result ) {
        $data = array();

        while ( $rows = $result->getNext() ) {
            foreach ( $rows as $field ) {
                $propertyLabel = $field->getPrintRequest()->getLabel();

                $server_name = $field->getResultSubject()->getTitle();
                $server_name = strtolower($server_name->getFullText());

                while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {
                    if ($propertyLabel == "Has tunnel IPv4") {
                        $ip_address = $dataValue->getWikiValue();
                        $data[$server_name]["ip_address"] = $ip_address;
                    } else if ($propertyLabel == "Has fqdn") {
                        $fqdn = strtolower($dataValue->getWikiValue());
                        if (strlen($fqdn) == 0) {
                            $data[$server_name]["fqdn"] = $server_name;
                            $data[$server_name]["partial_fqdn"] = $server_name;
                            $data[$server_name]["group"] = $server_name;
                            continue;
                        }

                        // NOTE: we shouldn't hard code .srv.1024.lu here
                        $exclude_list = array('.srv', '.1024.lu');
                        $partial_fqdn = str_replace($exclude_list, "", $fqdn);
                        $partial_fqdn = explode(".", $partial_fqdn);

                        $group = '';

                        if (count($partial_fqdn) >= 1) {
                            $group = $partial_fqdn[0];
                            $hostname = '';
                            if (count($partial_fqdn) == 1) {
                                $hostname = $group;
                            } else {
                                $group = $partial_fqdn[1];
                                $hostname = $partial_fqdn[0];
                            }

                            $data[$server_name]["fqdn"] = $fqdn;
                            $data[$server_name]["partial_fqdn"] = implode(".", $partial_fqdn);
                            $data[$server_name]["group"] = $group;
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @see SMWResultPrinter::getParamDefinitions
     *
     * @since 1.8
     *
     * @param $definitions array of IParamDefinition
     *
     * @return array of IParamDefinition|array
     */
    public function getParamDefinitions( array $definitions ) {
        $params = parent::getParamDefinitions( $definitions );

        $params['gateway'] = array(
            'message' => 'The node that will be used as a gateway',
            'default' => '',
        );

        return $params;
    }

    private function generateDiagCode( $nodes, $gateway ) {
        $diagCode = 'nwdiag {' . PHP_EOL;

        // draw gateway
        $match = $this->findGateway( $nodes, $gateway );
        if ($match != "") {
            $diagCode .= "\t" . $match[1] . " [shape = ellipse];" . PHP_EOL;
            $diagCode .= "\t" . $match[1] . " -- " . implode(".", $match) . "\n\n";
        }

        // draw network
        $diagCode .= "\tnetwork vpn {\n";
        foreach ( $nodes as $node ) {
            $ip = $node['ip_address'];
            $partial_fqdn = $node['partial_fqdn'];
            $diagCode .= "\t\t$partial_fqdn [address=\"$ip\"];" . PHP_EOL;
        }
        $diagCode .= "\t}" . PHP_EOL;

        // draw groups
        $groups = $this->findNodeGroups( $nodes );
        foreach ( array_keys($groups) as $group ) {
            if ($group == '') {
                continue;
            }

            // don't create a group if there is only one node inside it.
            if ($groups[$group] == 1) {
                continue;
            }

            $diagCode .= "\tgroup $group {\n";
            $diagCode .= "\t\tcolor = \"" . $this->randomColor() . "\";\n";
            $diagCode .= "\n";

            foreach ( $nodes as $node ) {
                if ( $node['group'] == $group ) {
                    $diagCode .= "\t\t" . $node['partial_fqdn'] . ";" . PHP_EOL;
                }
            }
            $diagCode .= "\t}\n\n";
        }
        $diagCode .= "}\n";


        //str_replace("\n", "<br>", $)

        echo $diagCode;
        exit();

        return $diagCode;
    }

    // walk through the $nodes array to find all groups with
    private function findNodeGroups( $nodes ) {
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

    private function findGateway( $nodes, $gateway ) {
        $match = null;
        foreach ($nodes as $node) {
            $partial_fqdn = $node['partial_fqdn'];
            if (strpos($partial_fqdn, $gateway) !== false) {
                $match = explode(".", $partial_fqdn);
            }
        }
        return $match;
    }

    public function randomColor() {
        $color = '#';
        for ($i = 0; $i <= 2; $i++) {
            $color .= dechex(mt_rand(0xAA, 0xFF));
        }
        return $color;
    }
}
