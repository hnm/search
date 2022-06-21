(function ($) {
    var Search = (function () {
        function Search(entryList, inputJqs) {
            this.lastSearch = "";
            this.entryList = entryList;
            this.entryListJq = this.entryList.getElemJq();
            this.fallbackElemJq = null;
            this.inputJqs = inputJqs;
            this.entryListJq.data("search", this);
            var that = this;
            for (var i = 0; i < this.inputJqs.length; i++) {
                var inputJq = $(this.inputJqs[i]);
                inputJq.keyup(function (e) {
                    var value = $(this).val();
                    if (value.length <= 2) {
                        that.entryList.hide();
                        return false;
                    }
                    ;
                    if (e.keyCode == 13) {
                        if (that.lastSearch != value) {
                            clearTimeout(that.statTimeout);
                            that.requestEntries(value, true);
                        }
                    }
                    if (e.keyCode === 27)
                        return;
                    that.search(value);
                });
                inputJq.focus(function () {
                    var value = $(this).val();
                    if (value.length > 2) {
                        that.entryList.show();
                    }
                });
            }
            ;
            $("body").keyup(function (e) {
                if (e.keyCode == 27) {
                    that.entryList.hide();
                }
            });
        }
        Search.prototype.search = function (searchStr) {
            if (searchStr === this.lastSearch)
                return;
            this.lastSearch = searchStr;
            if (searchStr) {
                clearTimeout(this.statTimeout);
                clearTimeout(this.timeout);
            }
            var that = this;
            this.statTimeout = setTimeout(function () {
                if (searchStr.length > 0) {
                    that.requestEntries(searchStr, true);
                }
            }, 1000);
            this.timeout = setTimeout(function () {
                if (searchStr.length > 0) {
                    that.requestEntries(searchStr);
                    that.entryList.show();
                }
                else {
                    that.entryList.hide();
                }
            }, 250);
        };
        Search.prototype.requestEntries = function (searchStr, writeStat) {
            if (writeStat === void 0) { writeStat = false; }
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
        };
        Search.prototype.getEntryList = function () {
            return this.entryList;
        };
        Search.prototype.beforeShow = function (func) {
            this.entryList.addCallback(EntryListCallbackType.BEFORE_SHOW, func);
        };
        Search.prototype.afterShow = function (func) {
            this.entryList.addCallback(EntryListCallbackType.AFTER_SHOW, func);
        };
        Search.prototype.beforeHide = function (func) {
            this.entryList.addCallback(EntryListCallbackType.BEFORE_HIDE, func);
        };
        Search.prototype.afterHide = function (func) {
            this.entryList.addCallback(EntryListCallbackType.AFTER_HIDE, func);
        };
        return Search;
    }());
    var Entry = (function () {
        function Entry(title, description, urlStr) {
            this.title = title;
            this.description = description;
            this.urlStr = urlStr;
        }
        return Entry;
    }());
    var EntryList = (function () {
        function EntryList(elemJq) {
            this.callbacks = {};
            this.visible = false;
            this.fallbackDiv = null;
            this.elemJq = elemJq;
            this.resultListJq = this.elemJq.find(".search-result-list");
            this.hideJq = $(this.elemJq.data("jqSearchHideSelector"));
            this.hide();
            var that = this;
            this.elemJq.find(".search-result-close").click(function (e) {
                that.hide();
            });
        }
        EntryList.prototype.show = function () {
            if (this.visible)
                return;
            this.visible = true;
            this.executeCallbacks(EntryListCallbackType.BEFORE_SHOW);
            this.elemJq.show();
            this.hideJq.hide();
            this.executeCallbacks(EntryListCallbackType.AFTER_SHOW);
        };
        EntryList.prototype.hide = function () {
            if (!this.visible)
                return;
            this.visible = false;
            this.executeCallbacks(EntryListCallbackType.BEFORE_HIDE);
            this.elemJq.hide();
            this.hideJq.show();
            this.executeCallbacks(EntryListCallbackType.AFTER_HIDE);
        };
        EntryList.prototype.buildUrl = function (searchStr, writeStat) {
            var url = this.elemJq.data("url") + "&ss=" + encodeURI(searchStr);
            if (writeStat) {
                url = url + "&stat=1";
            }
            if (this.elemJq.data("append-search-string")) {
                url = url + "&as=1";
            }
            return url;
        };
        EntryList.prototype.update = function (data) {
            var resultData = $(data);
            this.resultListJq.replaceWith(resultData);
            this.resultListJq = resultData;
            var resultAmount = this.resultListJq.data("search-found-amount");
            this.elemJq.find(".search-result-num").text(resultAmount);
            if (resultAmount === 0) {
                if (this.fallbackDiv === null) {
                    this.fallbackDiv = $("<span class='search-no-results-fallback'>"
                        + this.elemJq.get(0).dataset.searchFallback + "</span>");
                    this.resultListJq.parent().append(this.fallbackDiv);
                }
                else {
                    this.fallbackDiv.show();
                }
            }
            else {
                if (this.fallbackDiv !== null) {
                    this.fallbackDiv.hide();
                }
            }
        };
        EntryList.prototype.addCallback = function (type, callback) {
            if (!this.callbacks[type]) {
                this.callbacks[type] = [];
            }
            this.callbacks[type].push(callback);
        };
        EntryList.prototype.executeCallbacks = function (type) {
            if (!this.callbacks[type])
                return;
            for (var i in this.callbacks) {
                this.callbacks[type][i]();
            }
        };
        EntryList.prototype.getElemJq = function () {
            return this.elemJq;
        };
        return EntryList;
    }());
    (function ($) {
        $.fn["search"] = function (args) {
            for (var i = 0; i < this.length; i++) {
                var search = $($(this).get(i)).data("searchObj");
                if (search)
                    return search;
                if (args && args["inputs"])
                    return new Search(new EntryList(this), args["inputs"]);
            }
            return null;
        };
    })(jQuery);
    var EntryListCallbackType;
    (function (EntryListCallbackType) {
        EntryListCallbackType[EntryListCallbackType["BEFORE_SHOW"] = 0] = "BEFORE_SHOW";
        EntryListCallbackType[EntryListCallbackType["AFTER_SHOW"] = 1] = "AFTER_SHOW";
        EntryListCallbackType[EntryListCallbackType["BEFORE_HIDE"] = 2] = "BEFORE_HIDE";
        EntryListCallbackType[EntryListCallbackType["AFTER_HIDE"] = 3] = "AFTER_HIDE";
    })(EntryListCallbackType || (EntryListCallbackType = {}));
    $(document).ready(function ($) {
        $(".search-result-box").each(function () {
            var searchResultBox = $(this);
            var srbGroupKey = searchResultBox.data("searchGroupKey");
            var inputs = [];
            $(".search-input").each(function () {
                var input = $(this);
                input.val(null);
                if (JSON.stringify(srbGroupKey) === JSON.stringify(input.data("searchGroupKey"))) {
                    inputs.push(input);
                }
            });
            var elem = $(this);
            elem.search({ inputs: inputs });
        });
    });
})(jQuery);
//# sourceMappingURL=search.js.map