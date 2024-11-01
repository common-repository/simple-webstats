(() => {
	
	function recordView() {
		if(!swstatsConfig.viewNonce || !swstatsConfig.responseCode) return false;
		let data = {
			action: 'swstats_post_view',
			nonce: swstatsConfig.viewNonce,
			location: window.location.pathname,
			referrer: document.referrer
		};
		if((swstatsConfig.responseCode >= 200) && (swstatsConfig.responseCode < 300)) sendData(data);
	}
	
	function sendData(data, callback) {
		if(!swstatsConfig.ajaxURL) return false;
		let dataArray = [];
		Object.keys(data).forEach(key => dataArray.push(encodeURIComponent(key)+'='+encodeURIComponent(data[key])));
		let dataString = dataArray.join('&');
		let request = new XMLHttpRequest();
		request.open('POST', swstatsConfig.ajaxURL, true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		request.onload = function() {
			if (this.status >= 200 && this.status < 400) {
				let responseObj = false;
				if(this.response) responseObj = JSON.parse(this.response);
				if(responseObj.error) console.log('Simple Webstats Error: '+responseObj.error);
				if(callback && responseObj.data) callback(responseObj.data);
			}
		};
		request.onerror = function() {
			console.log('Simple Webstats: Connection Error');
		};
		request.send(dataString);
	}
	
	recordView();
	
})();