//$ = jQuery;
(function($) {
	class Search {
		private entryList: EntryList;
		private entryListJq: JQuery;
		private inputJqs: Array<JQuery>;
		private timeout;
		private statTimeout;
		private lastSearch = "";
		private fallbackElemJq;

		public constructor(entryList: EntryList, inputJqs: Array<JQuery>) {
			this.entryList = entryList;
			this.entryListJq = this.entryList.getElemJq();
			this.fallbackElemJq = null;
			this.inputJqs = inputJqs;

			this.entryListJq.data("search", this);

			var that = this;
			for (var i = 0; i < this.inputJqs.length; i++) {
				var inputJq = $(this.inputJqs[i]);
				inputJq.on('keyup change', function (e) {
					var value = <string> $(this).val();
					if (value.length <= 2) {
						that.entryList.hide();
						return false;
					};
					if (e.keyCode == 13) {
						if (that.lastSearch != value) {
							clearTimeout(that.statTimeout);
							that.requestEntries(value, true);
						}
					}

					if (e.keyCode === 27) return;
					that.search(value);
				})

				inputJq.focus(function() {
					var value = <string> $(this).val();
					if (value.length > 2) {
						that.entryList.show();
					}
				});
			};

			$("body").on('keyup change', function (e) {
				if (e.keyCode == 27 && that.entryList.isHideOnEsc()) {
					that.entryList.hide();
				}
			})
		}

		public search(searchStr: string) {
			if (searchStr === this.lastSearch) {
				this.entryList.show();
				return;
			}

			this.lastSearch = searchStr;

			if (searchStr) {
				clearTimeout(this.statTimeout);
				clearTimeout(this.timeout);
			}

			var that = this;
			this.statTimeout = setTimeout(function() {
				if (searchStr.length > 0) {
					that.requestEntries(searchStr, true);
				}
			}, 1000)

			this.timeout = setTimeout(function() {
				if (searchStr.length > 0) {
					that.requestEntries(searchStr);
					that.entryList.show();
				} else {
					that.entryList.hide();
				}
			}, 250);
		}

		public requestEntries(searchStr: string, writeStat: boolean = false) {
			var url = this.entryList.buildUrl(searchStr, writeStat);
			var entryList = this.entryList;

			var that = this;
			$.ajax({
				url: url,
				success: function (data) {
					if (!writeStat) {
						entryList.update(data);
					}
				}
			});
		}

		public getEntryList() {
			return this.entryList;
		}

		public beforeShow(func) {
			this.entryList.addCallback(EntryListCallbackType.BEFORE_SHOW, func);
		}

		public afterShow(func) {
			this.entryList.addCallback(EntryListCallbackType.AFTER_SHOW, func);
		}

		public beforeHide(func) {
			this.entryList.addCallback(EntryListCallbackType.BEFORE_HIDE, func);
		}

		public afterHide(func) {
			this.entryList.addCallback(EntryListCallbackType.AFTER_HIDE, func);
		}
	}

	class Entry {
		private title: string;
		private description: string;
		private urlStr: string;

		public constructor(title: string, description: string, urlStr: string) {
			this.title = title;
			this.description = description;
			this.urlStr = urlStr;
		}
	}

	class EntryList {
		private callbacks = {};
		private visible: boolean = false;
		private elemJq: JQuery;
		private resultListJq: JQuery;
		private hideJq: JQuery;
		private fallbackDiv: JQuery = null;
		private hideOnEsc = true;

		public constructor(elemJq: JQuery) {
			this.elemJq = elemJq;
			if (this.elemJq.data("hideOnEsc") === false) {
				this.hideOnEsc = false;
			}
			this.resultListJq = this.elemJq.find(".search-result-list");
			this.hideJq = $(this.elemJq.data("jqSearchHideSelector"));

			this.hide();

			var that = this;
			this.elemJq.find(".search-result-close").click(function(e) {
				that.hide();
			});
		}

		public show() {
			if (this.visible) return;
			this.visible = true;
			this.executeCallbacks(EntryListCallbackType.BEFORE_SHOW);

			this.elemJq.show();
			this.hideJq.hide();

			this.executeCallbacks(EntryListCallbackType.AFTER_SHOW);
		}

		public hide() {
			if (!this.visible) return;
			this.visible = false;
			this.executeCallbacks(EntryListCallbackType.BEFORE_HIDE);

			this.elemJq.hide();
			this.hideJq.show();

			this.executeCallbacks(EntryListCallbackType.AFTER_HIDE);
		}

		public buildUrl(searchStr: string, writeStat: boolean) {
			var url = this.elemJq.data("url") + "&ss=" + encodeURI(searchStr);
			if (writeStat) {
				url = url + "&stat=1";
			}
			if (this.elemJq.data("append-search-string")) {
				url = url + "&as=1";
			}

			return url;
		}

		public update(data) {
			var resultData = $(data);
			this.resultListJq.replaceWith(resultData);
			this.resultListJq = resultData;

			var resultAmount = this.resultListJq.data("search-found-amount")

			this.elemJq.find(".search-result-num").text(resultAmount);

			if (resultAmount === 0) {
				if (this.fallbackDiv === null) {
					this.fallbackDiv = $("<span class='search-no-results-fallback'>"
							+ this.elemJq.get(0).dataset.searchFallback + "</span>");

					this.resultListJq.parent().append(this.fallbackDiv);
				} else {
					this.fallbackDiv.show();
				}
			} else {
				if (this.fallbackDiv !== null) {
					this.fallbackDiv.hide();
				}
			}
		}

		public addCallback(type, callback) {
			if (!this.callbacks[type]) {
				this.callbacks[type] = [];
			}
			this.callbacks[type].push(callback);
		}

		private executeCallbacks(type) {
			if (!this.callbacks[type]) return;

			for (let i in this.callbacks) {
				this.callbacks[type][i]();
			}
		}

		public getElemJq() {
			return this.elemJq;
		}

		public isHideOnEsc() {
			return this.hideOnEsc;
		}
	}

	(function($) {
		$.fn["search"] = function(args: Object) {

			for (var i = 0; i < this.length; i++) {
				var search = $($(this).get(i)).data("searchObj");
				if (search) return search;

				if (args && args["inputs"]) return new Search(new EntryList(this), args["inputs"]);
			}

			return null;
		}
	})(jQuery);

	enum EntryListCallbackType {
		BEFORE_SHOW,
		AFTER_SHOW,
		BEFORE_HIDE,
		AFTER_HIDE
	}

	$(document).ready(function($) {
		$(".search-result-box").each(function() {
			var searchResultBox = $(this);
			var srbGroupKey = searchResultBox.data("searchGroupKey");
			var inputs = [];

			$(".search-input").each(function() {
				var input = $(this);
				input.val(null);

				if (JSON.stringify(srbGroupKey) === JSON.stringify(input.data("searchGroupKey"))) {
					inputs.push(input);
				}
			})
			var elem: any =  $(this);
			elem.search({inputs: inputs});
		});
	});
})(jQuery);