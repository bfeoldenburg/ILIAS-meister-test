<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Export/classes/class.ilXmlImporter.php");

/**
 * Importer class for files
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @version $Id: $
 * @ingroup ModulesGlossary
 */
class ilGlossaryImporter extends ilXmlImporter
{
	/**
	 * Initialisation
	 */
	function init()
	{
		include_once("./Modules/Glossary/classes/class.ilGlossaryDataSet.php");
		$this->ds = new ilGlossaryDataSet();
		$this->ds->setDSPrefix("ds");
		$this->config = $this->getImport()->getConfig("Modules/Glossary");
	}

	/**
	 * Import XML
	 *
	 * @param
	 * @return
	 */
	function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping)
	{
		if ($a_entity == "glo")
		{
			// case i container
			if($new_id = $a_mapping->getMapping('Services/Container','objs',$a_id))
			{
				$newObj = ilObjectFactory::getInstanceByObjId($new_id,false);
	
				$xml_file = $this->getImportDirectory().'/'.basename($this->getImportDirectory()).'.xml';
				$GLOBALS['ilLog']->write(__METHOD__.': Using XML file '.$xml_file);
	
			}
			else if ($new_id = $a_mapping->getMapping('Modules/Glossary','glo', "new_id"))	// this mapping is only set by ilObjGlossaryGUI
			{
				$newObj = ilObjectFactory::getInstanceByObjId($new_id,false);
	
				$xml_file = $this->getImportDirectory().'/'.basename($this->getImportDirectory()).'.xml';
				$GLOBALS['ilLog']->write(__METHOD__.': Using XML file '.$xml_file);
			}
			else
			{
				// in the new version (5.1)  we are also here, but the following file should not exist
				// if being exported with 5.1 or higher
				$xml_file = $this->getImportDirectory().'/'.basename($this->getImportDirectory()).'.xml';
			}

			// old school import
			if (file_exists($xml_file))
			{
				if (!$this->config->getPre51Import())
				{
					include_once("./Modules/Glossary/exceptions/class.ilGlossaryOldImportException.php");
					throw new ilGlossaryOldImportException("Old glossary import file.");
				}

				include_once './Modules/LearningModule/classes/class.ilContObjParser.php';
				$contParser = new ilContObjParser(
					$newObj,
					$xml_file,
					basename($this->getImportDirectory())
				);

				$contParser->startParsing();

				ilObject::_writeImportId($newObj->getId(), $newObj->getImportId());

				// write term map for taxonomies to mapping object
				$term_map = $contParser->getGlossaryTermMap();
				foreach ($term_map as $k => $v)
				{
					$a_mapping->addMapping("Services/Taxonomy", "tax_item",
						"glo:term:".$k, $v);

					// this is since 4.3 does not export these ids but 4.4 tax node assignment needs it
					$a_mapping->addMapping("Services/Taxonomy", "tax_item_obj_id",
						"glo:term:".$k, $newObj->getId());

					$a_mapping->addMapping("Services/AdvancedMetaData", "advmd_sub_item",
						"advmd:term:".$k, $v);
				}

				$a_mapping->addMapping("Modules/Glossary", "glo", $a_id, $newObj->getId());
				$a_mapping->addMapping("Services/AdvancedMetaData", "parent", $a_id, $newObj->getId());

				$this->current_glo = $newObj;
			}
			else
			{
				// necessary?
				// ilObject::_writeImportId($newObj->getId(), $newObj->getImportId());
				include_once("./Services/DataSet/classes/class.ilDataSetImportParser.php");
				$parser = new ilDataSetImportParser($a_entity, $this->getSchemaVersion(),
					$a_xml, $this->ds, $a_mapping);

				// in the new version the mapping above is done in the dataset
			}


		}
	}
	
	/**
	 * Final processing
	 *
	 * @param
	 * @return
	 */
	function finalProcessing($a_mapping)
	{
//echo "<pre>".print_r($a_mapping, true)."</pre>"; exit;
		// get all glossaries of the import
		include_once("./Services/Taxonomy/classes/class.ilObjTaxonomy.php");
		$maps = $a_mapping->getMappingsOfEntity("Modules/Glossary", "glo");
		foreach ($maps as $old => $new)
		{
			if ($old != "new_id" && (int) $old > 0)
			{
				// get all new taxonomys of this object
				$new_tax_ids = $a_mapping->getMapping("Services/Taxonomy", "tax_usage_of_obj", $old);
				if($new_tax_ids !== false)
				{
					$tax_ids = explode(":", $new_tax_ids);
					foreach ($tax_ids as $tid)
					{
						ilObjTaxonomy::saveUsage($tid, $new);
					}
				}

				// advmd column order
				if ($this->exportedFromSameInstallation())
				{
					include_once("./Modules/Glossary/classes/class.ilGlossaryAdvMetaDataAdapter.php");
					$advmdco = $a_mapping->getMappingsOfEntity("Modules/Glossary", "advmd_col_order");
					foreach ($advmdco as $id => $order)
					{
						$id = explode(":", $id);
						if ($id[0] == $old)
						{
							ilGlossaryAdvMetaDataAdapter::writeColumnOrder($new, $id[1], $order);
						}
					}
				}
			}
		}

	}
	
}
?>