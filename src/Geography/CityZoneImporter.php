<?php

namespace AppBundle\Geography;

use AppBundle\Entity\CityZone;
use AppBundle\Geography\CityZoneImporter;

class CityZoneImporter
{
	/**
	 * @param CityZoneImporterInterface[]
	 */
	public function __construct(private array $importers)
	{}

	/**
	 * @return CityZone[]
	 */
	public function import(string $url, string $type): array
	{
		if (isset($this->importers[$type])) {

			return $this->importers[$type]->import($url);
		}

		throw new \InvalidArgumentException(sprintf('Unknow importer "%s"', $type));
	}
}
