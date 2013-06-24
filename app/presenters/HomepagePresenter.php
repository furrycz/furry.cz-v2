<?php

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	public function renderDefault()
	{
		$cmsUtils = new Fcz\CmsUtilities($this);
		$this->template->homepageCmsHtml = $cmsUtils->getCmsHtml('homepage');
	}

}
