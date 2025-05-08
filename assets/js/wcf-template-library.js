function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return e; }; var t, e = {}, r = Object.prototype, n = r.hasOwnProperty, o = Object.defineProperty || function (t, e, r) { t[e] = r.value; }, i = "function" == typeof Symbol ? Symbol : {}, a = i.iterator || "@@iterator", c = i.asyncIterator || "@@asyncIterator", u = i.toStringTag || "@@toStringTag"; function define(t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e]; } try { define({}, ""); } catch (t) { define = function define(t, e, r) { return t[e] = r; }; } function wrap(t, e, r, n) { var i = e && e.prototype instanceof Generator ? e : Generator, a = Object.create(i.prototype), c = new Context(n || []); return o(a, "_invoke", { value: makeInvokeMethod(t, r, c) }), a; } function tryCatch(t, e, r) { try { return { type: "normal", arg: t.call(e, r) }; } catch (t) { return { type: "throw", arg: t }; } } e.wrap = wrap; var h = "suspendedStart", l = "suspendedYield", f = "executing", s = "completed", y = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var p = {}; define(p, a, function () { return this; }); var d = Object.getPrototypeOf, v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods(t) { ["next", "throw", "return"].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t); }); }); } function AsyncIterator(t, e) { function invoke(r, o, i, a) { var c = tryCatch(t[r], t, o); if ("throw" !== c.type) { var u = c.arg, h = u.value; return h && "object" == _typeof(h) && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) { invoke("next", t, i, a); }, function (t) { invoke("throw", t, i, a); }) : e.resolve(h).then(function (t) { u.value = t, i(u); }, function (t) { return invoke("throw", t, i, a); }); } a(c.arg); } var r; o(this, "_invoke", { value: function value(t, n) { function callInvokeWithMethodAndArg() { return new e(function (e, r) { invoke(t, n, e, r); }); } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(e, r, n) { var o = h; return function (i, a) { if (o === f) throw new Error("Generator is already running"); if (o === s) { if ("throw" === i) throw a; return { value: t, done: !0 }; } for (n.method = i, n.arg = a;;) { var c = n.delegate; if (c) { var u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u; } } if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg); } else "return" === n.method && n.abrupt("return", n.arg); o = f; var p = tryCatch(e, r, n); if ("normal" === p.type) { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done }; } "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg); } }; } function maybeInvokeDelegate(e, r) { var n = r.method, o = e.iterator[n]; if (o === t) return r.delegate = null, "throw" === n && e.iterator["return"] && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; var i = tryCatch(o, e.iterator, r.arg); if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y; var a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y); } function pushTryEntry(t) { var e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e); } function resetTryEntry(t) { var e = t.completion || {}; e.type = "normal", delete e.arg, t.completion = e; } function Context(t) { this.tryEntries = [{ tryLoc: "root" }], t.forEach(pushTryEntry, this), this.reset(!0); } function values(e) { if (e || "" === e) { var r = e[a]; if (r) return r.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) { var o = -1, i = function next() { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next; }; return i.next = i; } } throw new TypeError(_typeof(e) + " is not iterable"); } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) { var e = "function" == typeof t && t.constructor; return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name)); }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t; }, e.awrap = function (t) { return { __await: t }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this; }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); var a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next(); }); }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () { return this; }), define(g, "toString", function () { return "[object Generator]"; }), e.keys = function (t) { var e = Object(t), r = []; for (var n in e) r.push(n); return r.reverse(), function next() { for (; r.length;) { var t = r.pop(); if (t in e) return next.value = t, next.done = !1, next; } return next.done = !0, next; }; }, e.values = values, Context.prototype = { constructor: Context, reset: function reset(e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t); }, stop: function stop() { this.done = !0; var t = this.tryEntries[0].completion; if ("throw" === t.type) throw t.arg; return this.rval; }, dispatchException: function dispatchException(e) { if (this.done) throw e; var r = this; function handle(n, o) { return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o; } for (var o = this.tryEntries.length - 1; o >= 0; --o) { var i = this.tryEntries[o], a = i.completion; if ("root" === i.tryLoc) return handle("end"); if (i.tryLoc <= this.prev) { var c = n.call(i, "catchLoc"), u = n.call(i, "finallyLoc"); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); } else { if (!u) throw new Error("try statement without catch or finally"); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } } } }, abrupt: function abrupt(t, e) { for (var r = this.tryEntries.length - 1; r >= 0; --r) { var o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) { var i = o; break; } } i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null); var a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a); }, complete: function complete(t, e) { if ("throw" === t.type) throw t.arg; return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y; }, finish: function finish(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y; } }, "catch": function _catch(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.tryLoc === t) { var n = r.completion; if ("throw" === n.type) { var o = n.arg; resetTryEntry(r); } return o; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, "next" === this.method && (this.arg = t), y; } }, e; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * WCF Template Library Editor Core
 * @version 1.0.0
 */

/* global jQuery, WCF_Template_library_Editor*/

(function ($, window, document, config) {
  var Template_Library_data = {};
  var Template_Library_Chunk_data = [];
  // API for get requests
  var fetchRes = fetch("https://themecrowdy.com/wp-json/api/v1/list");
  var activePlugin = /*#__PURE__*/function () {
    var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
      return _regeneratorRuntime().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            _context.next = 2;
            return fetch(WCF_TEMPLATE_LIBRARY.ajaxurl, {
              method: "POST",
              headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                Accept: "application/json"
              },
              body: new URLSearchParams({
                action: "activate_from_editor_plugin",
                action_base: "animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php",
                nonce: WCF_TEMPLATE_LIBRARY.nonce
              })
            }).then(function (response) {
              return response.json();
            }).then(function (return_content) {
              if (return_content !== null && return_content !== void 0 && return_content.success) {
                window.location.reload();
              }
            });
          case 2:
          case "end":
            return _context.stop();
        }
      }, _callee);
    }));
    return function activePlugin() {
      return _ref.apply(this, arguments);
    };
  }();
  // FetchRes is the promise to resolve
  fetchRes.then(function (res) {
    return res.json();
  }).then(function (d) {
    Template_Library_data = d.library;
    Template_Library_data['template_types'] = WCF_TEMPLATE_LIBRARY.template_types;
    localStorage.setItem("aae_template_lib_data", Template_Library_data);
  });

  //get type specific templates
  var get_type_templates = function get_type_templates(type) {
    var templates = [];
    if (Template_Library_data['templates'] !== undefined) {
      Template_Library_data['templates'].forEach(function (template, index) {
        if (type === template.type) {
          var _WCF_TEMPLATE_LIBRARY, _WCF_TEMPLATE_LIBRARY2;
          if ((_WCF_TEMPLATE_LIBRARY = WCF_TEMPLATE_LIBRARY) !== null && _WCF_TEMPLATE_LIBRARY !== void 0 && (_WCF_TEMPLATE_LIBRARY = _WCF_TEMPLATE_LIBRARY.config) !== null && _WCF_TEMPLATE_LIBRARY !== void 0 && _WCF_TEMPLATE_LIBRARY.wcf_valid && ((_WCF_TEMPLATE_LIBRARY2 = WCF_TEMPLATE_LIBRARY) === null || _WCF_TEMPLATE_LIBRARY2 === void 0 || (_WCF_TEMPLATE_LIBRARY2 = _WCF_TEMPLATE_LIBRARY2.config) === null || _WCF_TEMPLATE_LIBRARY2 === void 0 ? void 0 : _WCF_TEMPLATE_LIBRARY2.wcf_valid) === true) {
            template['valid'] = 'yes';
          }
          templates.push(template);
        }
      });
    }
    return templates.reverse();
  };

  //get specific category templates
  var get_category_templates = function get_category_templates() {
    var category = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    var type = arguments.length > 1 ? arguments[1] : undefined;
    var type_templates = get_type_templates(type);
    var templates = type_templates;
    if (type_templates.length && '' !== category) {
      templates = [];
      var _iterator = _createForOfIteratorHelper(type_templates),
        _step;
      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var template = _step.value;
          //if template has no category
          if ('' === template.subtype) {
            continue;
          }
          var categories = template.subtype.split(",");
          if (categories.includes(category)) {
            templates.push(template);
          }
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }
    }
    Template_Library_Chunk_data = aaetemplate_chunkArray(templates, 30);
    return Template_Library_Chunk_data.shift();
  };

  //get specific category templates
  var search_category_templates = function search_category_templates() {
    var text = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    var type = arguments.length > 1 ? arguments[1] : undefined;
    var types = $('#elementor-template-library-header-menu .elementor-active').attr('data-tab') || 'block';
    var type_templates = get_type_templates(types);
    var templates = type_templates;
    if (type_templates.length && '' !== text) {
      templates = [];
      var _iterator2 = _createForOfIteratorHelper(type_templates),
        _step2;
      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var template = _step2.value;
          //if template has no category
          if ('' === template.subtype) {
            continue;
          }
          text = text.toLowerCase();
          if (template.title.toLowerCase().includes(text)) {
            templates.push(template);
          }
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }
    }
    Template_Library_Chunk_data = aaetemplate_chunkArray(templates, 30);
    return Template_Library_Chunk_data.shift();
  };
  var aaetemplate_chunkArray = function aaetemplate_chunkArray(array, chunkSize) {
    var result = [];
    for (var i = 0; i < array.length; i += chunkSize) {
      result.push(array.slice(i, i + chunkSize));
    }
    return result;
  };

  //get specific categories
  var get_categories = function get_categories(type) {
    var type_categories = new Set();
    var all_categories = [];
    var type_templates = get_type_templates(type);
    if (type_templates.length) {
      var _iterator3 = _createForOfIteratorHelper(type_templates),
        _step3;
      try {
        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
          var template = _step3.value;
          //if template has no category
          if ('' === template.subtype) {
            continue;
          }
          var categories = template.subtype.split(",");
          categories.forEach(function (sca) {
            type_categories.add(parseInt(sca));
          });
        }
      } catch (err) {
        _iterator3.e(err);
      } finally {
        _iterator3.f();
      }
    }
    var _iterator4 = _createForOfIteratorHelper(Template_Library_data.categories),
      _step4;
    try {
      for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
        var item = _step4.value;
        var _iterator5 = _createForOfIteratorHelper(type_categories),
          _step5;
        try {
          for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
            var category = _step5.value;
            if (item.id === category) {
              all_categories.push(item);
              break;
            }
          }
        } catch (err) {
          _iterator5.e(err);
        } finally {
          _iterator5.f();
        }
      }
    } catch (err) {
      _iterator4.e(err);
    } finally {
      _iterator4.f();
    }
    return all_categories;
  };
  $("document").ready(function () {
    var templateAddSection = $("#tmpl-elementor-add-section");
    if (0 < templateAddSection.length) {
      var oldTemplateButton = templateAddSection.html();
      oldTemplateButton = oldTemplateButton.replace('<div class="elementor-add-section-drag-title', '<div class="elementor-add-section-area-button elementor-add-wcf-template-button"></div><div class="elementor-add-section-drag-title');
      templateAddSection.html(oldTemplateButton);
    }
    elementor.on("preview:loaded", function () {
      $(elementor.$previewContents[0].body).on("click", ".elementor-add-wcf-template-button", function (event) {
        event.preventDefault();
        window.wcftmLibrary = elementorCommon.dialogsManager.createWidget("lightbox", {
          id: "wcf-template-library",
          onShow: function onShow() {
            this.getElements("widget").addClass("elementor-templates-modal");
            this.getElements("header").remove();
            this.getElements("message").remove();
            this.getElements("buttonsWrapper").remove();
            var t = this.getElements("widgetContent");
            render_popup(t);
          },
          onHide: function onHide() {
            window.wcftmLibrary.destroy();
          }
        });
        window.wcftmLibrary.getElements("header").remove();
        window.wcftmLibrary.show();
        $(window).trigger("resize"); //fixed modal position

        var active_menu_first_load = 0;
        function render_popup(t) {
          var tmpTypes = wp.template('wcf-templates-header');
          content = null;
          content = tmpTypes({
            template_types: Template_Library_data.template_types
          });
          t.html(content);

          //active menu
          active_menu(t);

          //category select
          selected_category(t);
          render_single_template(t);
          search_function();
          template_import();
        }
        function render_templates(t, activeMenu) {
          var category = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
          var templates = wp.template('wcf-templates');
          contents = null;
          contents = templates({
            templates: get_category_templates(category, activeMenu),
            categories: get_categories(activeMenu)
          });
          t.append(contents);
          aaeadddon_run_lazy_load();
          var is_loading = true;
          loading(is_loading);
          $($('.wcf-library-template').last()).find('img').on('load', function () {
            is_loading = false;
            loading(is_loading);
          });
        }
        function render_single_template(t) {
          // let template = $('.thumbnail');
          var backContent = $('#wcf-template-library .dialog-widget-content').html();
          $(document).on('click', '.thumbnail', function () {
            var _that = $(this);
            var template_id = _that.closest('.wcf-library-template').data('id');
            var template_url = _that.closest('.wcf-library-template').data('url');
            var singleTmp = wp.template('wcf-templates-single');
            content_single = null;
            content_single = singleTmp({
              template_link: template_url
            });
            t.html(content_single);
            //iframe is loaded
            var is_loading = true;
            loading(is_loading);
            $('#wcf-template-library iframe').on('load', function () {
              is_loading = false;
              loading(is_loading);
            });
            template_import(template_id);
          });

          //single back                   
          $(document).on('click', '#wcf-template-library-header-preview-back', function () {
            $('#wcf-template-library .dialog-widget-content').html(backContent);
            loading(false);
            //active menu
            active_menu(t);
            //category select
            selected_category(t);
            render_single_template(t);
            search_function();
            template_import();
          });

          //hide modal
          $(document).on('click', '.elementor-templates-modal__header__close', function () {
            window.wcftmLibrary.hide();
          });
        }
        function active_menu(t) {
          active_menu_first_load++;
          var menu_item = $('.wcf-template-library--header .elementor-template-library-menu-item');
          menu_item.click(function () {
            if ($(this).hasClass("elementor-active")) {
              return;
            }
            menu_item.removeClass("elementor-active");
            $(this).addClass("elementor-active");
            activeMenu = $(this).attr("data-tab");
            $(t).find('.dialog-message').remove();
            render_templates(t, activeMenu);

            //category select ensure dom selections
            selected_category(t);
            render_single_template(t);
            search_function();
            template_import();
          });

          //hide modal
          $('.elementor-templates-modal__header__close').on('click', function () {
            window.wcftmLibrary.hide();
          });
          if (active_menu_first_load >= 1) {
            return;
          }
          var activeMenu = $('.wcf-template-library--header .elementor-active').attr('data-tab');
          render_templates(t, activeMenu);
        }
        function selected_category(t) {
          $('#wcf-template-library-filter-subtype').on('change', function (e) {
            var activeMenu = $('.wcf-template-library--header .elementor-active').attr('data-tab');
            var valueSelected = this.value;
            $(t).find('.dialog-message').remove();
            render_templates(t, activeMenu, valueSelected);

            //selected
            $("#wcf-template-library-filter-subtype option[value='" + valueSelected + "']").attr("selected", "selected");
            selected_category(t);
            render_single_template(t);
            search_function();
            template_import();
          });
        }
        function search_function() {
          $('#wcf-template-library-filter-text').on('keyup', function () {
            var filter = this.value;
            var container = document.querySelector('.wcf-library-templates');
            var currentchunk = search_category_templates(filter);
            container.innerHTML = '';
            currentchunk.forEach(function (item) {
              var templateHtml = generateTemplate(item);
              container.innerHTML += templateHtml; // Add each generated HTML to the container
            });

            setTimeout(function () {
              var elements = $('.wcf-library-template');
              var re = new RegExp(filter, 'i');
              elements.each(function (x, element) {
                var title = $(element).find('.title')[0];
                if (re.test(title.textContent)) {
                  title.innerHTML = title.textContent.replace(re, '<b>$&</b>');
                }
              });
            }, 100);
          });
        }
        function template_import() {
          var id = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
          var is_loading = true;
          $(document).on('click', '.library--action.insert', function () {
            var _that = $(this);
            var template_id = id;
            if (null === template_id) {
              template_id = $(this).closest('.wcf-library-template').data('id');
            }
            loading(is_loading);
            _that.hide();
            window.wcftmLibrary.currentRequest = elementorCommon.ajax.addRequest("get_wcf_template_data", {
              unique_id: template_id,
              data: {
                edit_mode: !0,
                display: !0,
                template_id: template_id
              },
              success: function success(e) {
                $e.run("document/elements/import", {
                  model: window.elementor.elementsModel,
                  data: e
                });
                is_loading = false;
                window.wcftmLibrary.hide();
              }
            }).fail(function () {});
          });
        }
        function loading(is_loading) {
          var loading = $('.wcf-template-library--loading');
          if (!is_loading) {
            loading.hide();
            loading.attr("hidden");
          } else {
            loading.show();
            loading.removeAttr("hidden");
          }
        }
      });
    });
  });
  $(document).on('click', '.aaeplugin-activate', function (e) {
    e.preventDefault();
    var userConfirmed = confirm("Are you sure you want to activate plugin? Any unsaved changes will be lost. Please Save change.");
    if (userConfirmed) {
      activePlugin();
    }
  });
  function aaeadddon_run_lazy_load() {
    var listItems = document.querySelectorAll(".aaeaadon-loadmore-footer");
    var lastItem = listItems[listItems.length - 1];
    var observerOptions = {
      root: null,
      // Uses the viewport as the root
      rootMargin: "0px",
      threshold: 0.1 // Trigger when 10% of the element is visible
    };

    var observerCallback = function observerCallback(entries, observer) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var currentchunk = Template_Library_Chunk_data.shift();
          var container = document.querySelector('.wcf-library-templates');
          if (currentchunk) {
            currentchunk.forEach(function (item) {
              var templateHtml = generateTemplate(item);
              container.innerHTML += templateHtml; // Add each generated HTML to the container
            });
          }
        }
      });
    };

    var observer = new IntersectionObserver(observerCallback, observerOptions);
    observer.observe(lastItem);
  }
  ;
  var generateTemplate = function generateTemplate(item) {
    var pluginSlug = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php';
    var allPlugins = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
    var activePlugins = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : [];
    return "\n            <div class=\"wcf-library-template\" data-id=\"".concat(item.id, "\" data-url=\"").concat(item.url, "\">\n                <div class=\"thumbnail\">\n                    <img src=\"").concat(item.thumbnail, "\" alt=\"").concat(item.title, "\">\n                </div>\n                \n                ").concat(item !== null && item !== void 0 && item.valid && item.valid ? "\n                    <!-- Show the 'Insert' button if the template is valid -->\n                    <button class=\"library--action insert\">\n                        <i class=\"eicon-file-download\"></i>\n                        Insert\n                    </button>\n                " : "\n                    <!-- Show premium or activation buttons based on plugin status -->\n                    ".concat(!allPlugins.includes(pluginSlug) && !activePlugins.includes(pluginSlug) ? "\n                        <!-- Show 'Go Premium' button if the plugin is not installed -->\n                        <a href=\"https://animation-addons.com\" class=\"library--action pro\" target=\"_blank\">\n                            <i class=\"eicon-external-link-square\"></i>\n                            Go Premium\n                        </a>\n                    " : '', "\n                    ").concat(activePlugins.includes(pluginSlug) ? "\n                        <!-- Show 'Pro' button if the plugin is installed and active -->\n                        <button class=\"library--action pro\">\n                            <i class=\"eicon-external-link-square\"></i>\n                            Pro\n                        </button>\n                    " : '', "\n                    ").concat(allPlugins.includes(pluginSlug) && !activePlugins.includes(pluginSlug) ? "\n                        <!-- Show 'Activate' button if the plugin is installed but not active -->\n                        <button class=\"library--action pro aaeplugin-activate\">\n                            <i class=\"eicon-external-link-square\"></i>\n                            Activate\n                        </button>\n                    " : '', "\n                "), "\n                \n                <p class=\"title\">").concat(item.title, "</p>\n            </div>\n        ");
  };
})(jQuery, window, document);