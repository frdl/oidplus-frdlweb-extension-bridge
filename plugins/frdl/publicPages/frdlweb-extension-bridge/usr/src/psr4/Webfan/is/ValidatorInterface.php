<?php

namespace Webfan\is;

interface ValidatorInterface
{
	public function validate();	
	public function getType() : string;  		
}