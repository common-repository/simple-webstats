(() => {
	
	let periodSelect = document.getElementById('periodselect');
	let viewPanels = document.querySelectorAll('.swstats-dash .panels > div, #swstats-widget .panel');
	let viewRequests = Array(viewPanels.length).fill(null);
	
	// chart config
	Chart.defaults.font.family = '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif';
	Chart.defaults.font.size = 11;
	Chart.defaults.plugins.legend.display = false;
	Chart.defaults.color = 'rgba(255,255,255,.8)';
	Chart.defaults.maintainAspectRatio = false;
	Chart.defaults.borderColor = 'rgba(255,255,255,.1)';
	Chart.defaults.elements.point.pointStyle = false;
	Chart.defaults.elements.line.borderWidth = 2;
	Chart.defaults.elements.line.borderCapStyle = 'round';
	Chart.defaults.elements.line.borderJoinStyle = 'round';
	let chartConfig = {
		type: 'line',
		options: {
			scales: {
				x: {
					border: {
						display: false
					},
					grid: {
						display: false
					},
					ticks: {
						maxRotation: 0,
						minRotation: 0,
						autoSkipPadding: 15,
						padding: 5
					},
					afterTickToLabelConversion: function( scaleInstance ){
						scaleInstance.ticks[0].label = '';
						scaleInstance.ticks[scaleInstance.ticks.length-1].label = '';
					}
				},
				y: {
					border: {
						display: false,
					},
					beginAtZero: true,
					grid: {
						drawTicks: false,
					},
					position: 'right',
					ticks: {
						maxRotation: 0,
						minRotation: 0,
						precision: 0,
						padding: -10,
						mirror: true,
						z: 10,
						callback: (value, index, values) => {
							if(value == 0) return '';
							return format_number(value);	
						}
					}
				},
			},
		}
	};	
	
	// chart data
	function chart_data(chartdata, granularity) {
		return {
			labels: Object.keys(chartdata.visits[granularity]),
			datasets: [
				{
					label: chartdata.visitsLabel,
					data: chartdata.visits[granularity],
					borderColor: 'rgba(54, 162, 235, .8)',
					backgroundColor: 'rgba(54, 162, 235, .8)'
				},
				{
					label: chartdata.viewsLabel,
					data: chartdata.views[granularity],
					borderColor: 'rgba(150, 222, 150, .8)',
					backgroundColor: 'rgba(150, 222, 150, .8)'
				}
			]
		};
	}
	
	// load data for a view
	function load_view_data(viewPanel) {
		
		if(viewPanel.dataset.type && viewPanel.dataset.nonce) {
			
			let panelIndex = Array.prototype.indexOf.call(viewPanels, viewPanel);
			let panelType = viewPanel.dataset.type;
			let isSummaryPanel = (panelType === 'summary') ? true : false;
			let isWidget = (viewPanel.closest('#swstats-widget')) ? true : false;
			let panelInner = viewPanel.querySelector('.inner');
			let panelLoading = viewPanel.querySelector('.loading');
			panelInner.classList.remove('show');
			panelLoading.classList.add('show');
			if(!isSummaryPanel) {
				let noDataNotification = viewPanel.querySelector('.nodata');
				noDataNotification.classList.remove('show');
			}
			
			let data = {
				nonce: viewPanel.dataset.nonce
			};
			if(isWidget) {
				// default period for widget
				data['period'] = 'last24hours';
			}
			else {
				data['period'] = periodSelect.value;
			}
			if(isSummaryPanel) {
				data['action'] = 'swstats_get_summary_data';
				data['isWidget'] = isWidget;
			}
			else {
				data['action'] = 'swstats_get_table_data';
				data['dataType'] = panelType;
				data['viewID'] = viewPanel.dataset.viewid;
				data['numResults'] = 10;
			}
			
			if(viewRequests[panelIndex] && ('readyState' in viewRequests[panelIndex]) && (viewRequests[panelIndex].readyState < 4)) viewRequests[panelIndex].abort();
			
			let dataArray = [];
			Object.keys(data).forEach(key => dataArray.push(encodeURIComponent(key)+'='+encodeURIComponent(data[key])));
			let dataString = dataArray.join('&');
			viewRequests[panelIndex] = new XMLHttpRequest();
			viewRequests[panelIndex].open('POST', ajaxURL, true);
			viewRequests[panelIndex].setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			viewRequests[panelIndex].onload = function() {
				if (this.status >= 200 && this.status < 400) {
					let responseObj = false;
					if(this.response) responseObj = JSON.parse(this.response);
					if(responseObj.data && responseObj.newNonce) {
						viewPanel.dataset.nonce = responseObj.newNonce;
						if(isSummaryPanel) output_summary_data(viewPanel, responseObj.data, responseObj.chartdata);
						else output_table_data(viewPanel, responseObj.headers, responseObj.data, responseObj.count);
						panelInner.classList.add('show');
						panelLoading.classList.remove('show');
					}				
					else console.log('Simple Webstats Error: '+responseObj.error);
				}
				else console.log('Simple Webstats: Connection Error');
			};
			viewRequests[panelIndex].onerror = function() {
				console.log('Simple Webstats: Connection Error');
			};
			viewRequests[panelIndex].send(dataString);	
			
		}
		
	}
	
	// output data into a table
	function output_table_data(dataPanel, headers, data, count) {
		let type = dataPanel.dataset.type;
		if(dataPanel && type && headers && data) {
			let table = dataPanel.querySelector('.table');
			table.replaceChildren();
			let noDataNotification = dataPanel.querySelector('.nodata');
			if(data.length == 0) {
				noDataNotification.classList.add('show');
				return false;
			}
			noDataNotification.classList.remove('show');
			var headRow = document.createElement('div');
			headRow.classList.add('row', 'head', 'cols'+headers.length);
			headers.forEach(label => {
				var headCell = document.createElement('div');
				headCell.innerHTML = label;
				headRow.appendChild(headCell);
			});
			table.appendChild(headRow);
			data.forEach(row => {
				var bodyRow = document.createElement('div');
				bodyRow.classList.add('row', 'body', 'cols'+headers.length);
				if(count) {
					let barWidth = (Math.round(row[1] / count * 1000) / 10);
					bodyRow.style.backgroundImage = 'linear-gradient(90deg, rgba(255,255,255,.1) '+barWidth+'%, rgba(255,255,255,0) '+barWidth+'%)';
				}
				row.forEach((cell, index) => {
					var bodyCell = document.createElement('div');
					if((index == 0) && (type == 'countries')) {
						if(!swstats_countries[cell]) cell = '<span class="fflag fflag-NONE ff-sm"></span>Unknown';
						else cell = '<span class="fflag fflag-'+cell+' ff-sm"></span>'+swstats_countries[cell];
					}
					if(index == 0) bodyCell.innerHTML = cell;
					else bodyCell.innerHTML = format_number(cell);
					bodyRow.appendChild(bodyCell);
				});
				table.appendChild(bodyRow);
			});
			
		}
	}

	// output summary data
	function output_summary_data(summaryPanel, data, chartdata) {
		let isWidget = (summaryPanel.closest('#swstats-widget')) ? true : false;
		let panelInner = summaryPanel.querySelector('.inner');
		var output = '';
		for(const[key, dataPoint] of Object.entries(data)) {
			output += '<div class="'+key+'"><h3>'+dataPoint.label+'</h3><p>';
			if(dataPoint.value === null) output += '--';
			else if(dataPoint.format == 'number') output += format_number(dataPoint.value);
			else if(dataPoint.format == 'time') output += format_time(dataPoint.value);
			else if(dataPoint.format == 'percent') output += format_percent(dataPoint.value, 2);
			else output += dataPoint.value;
			if(dataPoint.comparison !== null) {
				let comp = compare_numbers(dataPoint.value, dataPoint.comparison);
				if(comp !== null) {
					if(key == 'bouncerate') output += format_comparison(comp, true);
					else output += format_comparison(comp);
				}
			}
			output += '</p></div>';	
		}
		panelInner.innerHTML = output;

		// output chart markup
		let chartWrapper = document.createElement('div');
		chartWrapper.classList.add('chart');
		const chart = document.createElement('canvas');
		chartWrapper.appendChild(chart);
		
		// output chart granularity controls
		if(!isWidget && (Object.keys(chartdata.granularities).length > 1)) {
			let granularityControls = document.createElement('div');
			granularityControls.classList.add('granularity');
			for (const[granularity, granularityLabel] of Object.entries(chartdata.granularities)) {
				let control = document.createElement('a');
				control.innerText = granularityLabel;
				control.href = '#';
				control.dataset.granularity = granularity;
				if(granularity == chartdata.defaultGranularity) control.classList.add('active');
				control.addEventListener('click', function(e){
					e.preventDefault();
					statsChart.data = chart_data(chartdata, this.dataset.granularity);
					statsChart.update();
					this.parentNode.querySelectorAll('a').forEach(el => {
						el.classList.remove('active');
					});
					this.classList.add('active');
					
				});
				granularityControls.appendChild(control);
			}
			chartWrapper.appendChild(granularityControls);
		}
		
		panelInner.appendChild(chartWrapper);
		
		// create chart
		chartConfig['data'] = chart_data(chartdata, chartdata.defaultGranularity);
		const statsChart = new Chart(chart, chartConfig);
			
	}
	
	// init dashboard
	function init_dashboard() {
		
		// trigger loading data for view panels
		viewPanels.forEach(el => { load_view_data(el); });
		
		// reload on period select change
		periodSelect.addEventListener('change', function(){
			viewPanels.forEach(el => { load_view_data(el); });
		});
		
		// table view switches
		const viewSwitches = document.querySelectorAll('.swstats-dash .data-panel .switches a');
		viewSwitches.forEach(viewSwitch => {
			viewSwitch.addEventListener('click', function(e){
				e.preventDefault();
				let allSwitches = viewSwitch.closest('.switches').querySelectorAll('a');
				let viewPanel = viewSwitch.closest('.data-panel');
				let h2 = viewPanel.querySelector('h2');
				viewPanel.dataset.viewid = viewSwitch.dataset.viewid;
				h2.innerText = viewSwitch.innerText;
				viewSwitch.style.display = 'none';
				allSwitches.forEach(el => {
					if(!viewSwitch.isEqualNode(el)) el.style.display = 'inline-block';
				});
				load_view_data(viewPanel);
			});
		});
		
	}
	
	// init widget
	function init_widget(widgetPanel) {
		
		// trigger loading data for view panels
		viewPanels.forEach(el => { load_view_data(el); });
		
	}
	
	// format numbers for output
	function format_number(num) {
		if(num >= 100000000) return parseFloat((num / 1000000).toFixed(0))+'M';
		else if(num >= 1000000) return parseFloat((num / 1000000).toFixed(1))+'M';
		else if(num >= 100000) return parseFloat((num / 1000).toFixed(0))+'K';
		else if(num >= 1000) return parseFloat((num / 1000).toFixed(1))+'K';
		else return parseFloat((num / 1).toFixed(2));
	}
	
	// format time for output
	function format_time(secs) {
		var secs = Math.round(secs);
		var mins = Math.floor(secs / 60);
		if(mins > 0) secs -= (mins * 60);
		return mins+'m '+String(secs).padStart(2, '0')+'s';
	}
	
	// format percentage for output
	function format_percent(percent, dp) {
		return parseFloat((percent * 100).toFixed(dp))+'%';
	}
	
	// numeric comparison
	function compare_numbers(currentVal, previousVal) {
		currentVal = parseFloat(currentVal);
		previousVal = parseFloat(previousVal);
		if((currentVal == 0) || (previousVal == 0)) return null;
		let diff = currentVal - previousVal;
		return diff / previousVal;
	}
	
	// format comparison for output
	function format_comparison(comp, invert_colours) {
		comp = parseFloat((comp * 100).toFixed(1));
		if(invert_colours !== true) invert_colours = false;
		let colour = ((!invert_colours && (comp >= 0)) || (invert_colours && (comp <= 0))) ? 'green' : 'red';
		let output = '<span class="comparison '+colour+'">';
		if(comp >= 0) output += '+';
		output += comp+'%</span>';
		return output;
	}
	
	// init dashboad page
	let dashboard = document.querySelector('.swstats-dash');
	if(dashboard && dashboard.dataset.ajaxurl) {
		var ajaxURL = dashboard.dataset.ajaxurl;
		init_dashboard();
	}
	
	// init dashboard widget
	let widgetPanel = document.querySelector('#swstats-widget .panel');
	if(widgetPanel && widgetPanel.dataset.ajaxurl) {
		
		// make widget clickable
		let widgetInside = document.querySelector('#swstats-widget .inside');
		if(widgetInside) widgetInside.addEventListener('click', function(e){
			e.preventDefault();
			window.location = 'admin.php?page=swstats_dash';
		});
		
		var ajaxURL = widgetPanel.dataset.ajaxurl;
		init_widget();
		
	}

})();