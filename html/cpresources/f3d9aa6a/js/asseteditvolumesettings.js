(function($) {

	var $urlField = $('.volume-url'),
		$s3AccessKeyIdInput = $('.s3-key-id'),
		$s3SecretAccessKeyInput = $('.s3-secret-key'),
		$s3BucketSelect = $('.s3-bucket-select > select'),
		$s3RefreshBucketsBtn = $('.s3-refresh-buckets'),
		$s3RefreshBucketsSpinner = $s3RefreshBucketsBtn.parent().next().children(),
		$s3Region = $('.s3-region'),
		refreshingS3Buckets = false;

	$s3RefreshBucketsBtn.click(function()
	{
		if ($s3RefreshBucketsBtn.hasClass('disabled'))
		{
			return;
		}

		$s3RefreshBucketsBtn.addClass('disabled');
		$s3RefreshBucketsSpinner.removeClass('hidden');

		var data = {
			params: {
				keyId:  $s3AccessKeyIdInput.val(),
				secret: $s3SecretAccessKeyInput.val()
			},
			volumeType: 'AwsS3',
			dataType:   'bucketList'
		};

		Craft.postActionRequest('volumes/load-volume-type-data', data, function(response, textStatus)
		{
			$s3RefreshBucketsBtn.removeClass('disabled');
			$s3RefreshBucketsSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.error)
				{
					alert(response.error);
				}
				else if (response.length > 0)
				{
					var currentBucket = $s3BucketSelect.val(),
						currentBucketStillExists = false;

					refreshingS3Buckets = true;

					$s3BucketSelect.prop('readonly', false).empty();

					for (var i = 0; i < response.length; i++)
					{
						if (response[i].bucket == currentBucket)
						{
							currentBucketStillExists = true;
						}

						$s3BucketSelect.append('<option value="'+response[i].bucket+'" data-url-prefix="'+response[i].urlPrefix+'" data-region="'+response[i].region+'">'+response[i].bucket+'</option>');
					}

					if (currentBucketStillExists)
					{
						$s3BucketSelect.val(currentBucket);
					}

					refreshingS3Buckets = false;

					if (!currentBucketStillExists)
					{
						$s3BucketSelect.trigger('change');
					}
				}
			}
		});
	});

	$s3BucketSelect.change(function()
	{
		if (refreshingS3Buckets)
		{
			return;
		}

		var $selectedOption = $s3BucketSelect.children('option:selected');

		$urlField.val($selectedOption.data('url-prefix'));
		$s3Region.val($selectedOption.data('region'));
	});



	var $rackspaceUsernameInput = $('.rackspace-username'),
		$rackspaceApiKeyInput = $('.racskspace-api-key'),
		$rackspaceRegionSelect = $('.rackspace-region-select > select'),
		$rackspaceContainerSelect = $('.rackspace-container-select > select'),
		$rackspaceRefreshContainersBtn = $('.rackspace-refresh-containers'),
		$rackspaceRefreshContainersSpinner = $rackspaceRefreshContainersBtn.parent().next().children(),
		refreshingRackspaceContainers = false;

	$rackspaceRefreshContainersBtn.click(function()
	{
		if ($rackspaceRefreshContainersBtn.hasClass('disabled'))
		{
			return;
		}

		$rackspaceRefreshContainersBtn.addClass('disabled');
		$rackspaceRefreshContainersSpinner.removeClass('hidden');

		var data = {
			params: {
				username: $rackspaceUsernameInput.val(),
				apiKey:   $rackspaceApiKeyInput.val(),
				region: $rackspaceRegionSelect.val()
			},
			volumeType: 'Rackspace',
			dataType:   'containerList'

		};

		Craft.postActionRequest('volumes/load-volume-type-data', data, function(response, textStatus)
		{
			$rackspaceRefreshContainersBtn.removeClass('disabled');
			$rackspaceRefreshContainersSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.error)
				{
					alert(response.error);
				}
				else if (response.length > 0)
				{
					var currentContainer = $rackspaceContainerSelect.val(),
						currentContainerStillExists = false;

					refreshingRackspaceContainers = true;

					$rackspaceContainerSelect.prop('readonly', false).empty();

					for (var i = 0; i < response.length; i++)
					{
						if (response[i].container == currentContainer)
						{
							currentContainerStillExists = true;
						}

						$rackspaceContainerSelect.append('<option value="'+response[i].container+'" data-urlprefix="'+response[i].urlPrefix+'">'+response[i].container+'</option>');
					}

					if (currentContainerStillExists)
					{
						$rackspaceContainerSelect.val(currentContainer);
					}

					refreshingRackspaceContainers = false;

					if (!currentContainerStillExists)
					{
						$rackspaceContainerSelect.trigger('change');
					}
				}
			}
		});
	});

	$rackspaceContainerSelect.change(function()
	{
		if (refreshingRackspaceContainers)
		{
			return;
		}

		var $selectedOption = $rackspaceContainerSelect.children('option:selected');

		$urlField.val($selectedOption.data('urlprefix'));
	});



	var $googleAccessKeyIdInput = $('.google-key-id'),
		$googleSecretAccessKeyInput = $('.google-secret-key'),
		$googleBucketSelect = $('.google-bucket-select > select'),
		$googleRefreshBucketsBtn = $('.google-refresh-buckets'),
		$googleRefreshBucketsSpinner = $googleRefreshBucketsBtn.parent().next().children(),
		refreshingGoogleBuckets = false;

	$googleRefreshBucketsBtn.click(function()
	{
		if ($googleRefreshBucketsBtn.hasClass('disabled'))
		{
			return;
		}

		$googleRefreshBucketsBtn.addClass('disabled');
		$googleRefreshBucketsSpinner.removeClass('hidden');

		var data = {
			params: {
				keyId:  $googleAccessKeyIdInput.val(),
				secret: $googleSecretAccessKeyInput.val()
			},
			volumeType: 'GoogleCloud',
			dataType:   'bucketList'
		};

		Craft.postActionRequest('volumes/load-volume-type-data', data, function(response, textStatus)
		{
			$googleRefreshBucketsBtn.removeClass('disabled');
			$googleRefreshBucketsSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.error)
				{
					alert(response.error);
				}
				else if (response.length > 0)
				{
					var currentBucket = $googleBucketSelect.val(),
						currentBucketStillExists = false;

					refreshingGoogleBuckets = true;

					$googleBucketSelect.prop('readonly', false).empty();

					for (var i = 0; i < response.length; i++)
					{
						if (response[i].bucket == currentBucket)
						{
							currentBucketStillExists = true;
						}

						$googleBucketSelect.append('<option value="'+response[i].bucket+'" data-url-prefix="'+response[i].urlPrefix+'">'+response[i].bucket+'</option>');
					}

					if (currentBucketStillExists)
					{
						$googleBucketSelect.val(currentBucket);
					}

					refreshingGoogleBuckets = false;

					if (!currentBucketStillExists)
					{
						$googleBucketSelect.trigger('change');
					}
				}
			}
		});
	});

	$googleBucketSelect.change(function()
	{
		if (refreshingGoogleBuckets)
		{
			return;
		}

		var $selectedOption = $googleBucketSelect.children('option:selected');

		$urlField.val($selectedOption.data('url-prefix'));
	});

	var changeExpiryValue = function ()
	{
		var parent = $(this).parents('.field'),
			amount = parent.find('.expires-amount').val(),
			period = parent.find('.expires-period select').val();

		var combinedValue = (parseInt(amount, 10) == 0 || period.length == 0) ? '' : amount + ' ' + period;

		parent.find('[type=hidden]').val(combinedValue);
	};

	$('.expires-amount').keyup(changeExpiryValue).change(changeExpiryValue);
	$('.expires-period select').change(changeExpiryValue);

})(jQuery);
