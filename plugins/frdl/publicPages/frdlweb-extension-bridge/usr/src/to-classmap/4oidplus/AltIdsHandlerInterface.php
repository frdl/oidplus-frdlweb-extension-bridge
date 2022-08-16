<?php
namespace frdl\OidplusTools;

interface AltIdsHandlerInterface
{
   public function getAltIdsInfo($id);
   public function handleAltIds($id, $insertMissing = false);	
}