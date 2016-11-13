<?php
/**
 * This file contains class::FitTrainingEffect
 * @package Runalyze\View\Activity\Box
 */

namespace Runalyze\View\Activity\Box;

use Runalyze\Activity;
use Runalyze\View\Activity\Context;

/**
 * Boxed value for FitTrainingEffect
 * 
 * @author Michael Pohl
 * @author Hannes Christiansen
 * @package Runalyze\View\Activity\Box
 */
class FitTrainingEffect extends ValueBox
{
	/**
	 * Constructor
	 * @param \Runalyze\View\Activity\Context $Context
	 */
	public function __construct(Context $Context)
	{
		parent::__construct(
			new Activity\TrainingEffect($Context->activity()->fitTrainingEffect())
		);
	}
}
