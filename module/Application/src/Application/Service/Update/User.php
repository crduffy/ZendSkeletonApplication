<?php

namespace Application\Service\Update;

use Application\Service\Update;

/**
 * service: user_update_service
 */
class User extends Update
{

	public function isBatch($batch = null)
	{
		return $this;
	}

}

