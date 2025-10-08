// assets/js/editor/nested-slider.js
// deps when enqueuing: ['elementor-editor','elementor-common','wp-element'] + footer=true
jQuery(function ($) {
	let didRegister = false;
  
	// Grab nested exports in a version-safe way
	const getNestedExports = () =>
	  ($e?.components?.get?.('nested-elements/nested-repeater') ||
	   $e?.components?.get?.('nested-elements'))?.exports || null;
  
	const registerOnce = () => {
	  if (didRegister) return true;
  
	  const E = getNestedExports();
	  if (!E) return false;
  
	  const BaseView  = E.NestedViewBase || E.NestedView;
	  const BaseModel = E.NestedRepeaterModel || E.NestedModelBase;
  
	  class View extends BaseView {
		filter(child, index) {
		  if (typeof child?.set === 'function') child.set('dataIndex', index + 1);
		  else if (child?.attributes) child.attributes.dataIndex = index + 1;
		  return true;
		}
		onAddChild(childView) {}
	  }
  
	  // If you keep JSX, ensure your build provides React (via wp-element)
	  function EmptyComponent() {
		return (
		  <div className="elementor-first-add">
			<div className="elementor-icon eicon-plus" onClick={() => $e.route('panel/elements/categories')} />
		  </div>
		);
	  }
  
	  class AAENestedSlider extends elementor.modules.elements.types.Base {
		getType() { return 'wcf--nested-slider'; }
		getView() { return View; }
		getEmptyView() { return EmptyComponent; } // React component is OK in the editor
		getModel() { return BaseModel; }          // nested-enabled model
		getInitialConfig() {
		  return {
			...super.getInitialConfig(),
			defaults: { repeater_title_setting: 'slide_title' },
		  };
		}
	  }
  
	  elementor.elementsManager.registerElementType(new AAENestedSlider());
	  didRegister = true;	  
	  return true;
	};
  
	// 1) Primary, modern path: editor components are initializing
	elementorCommon.elements.$window.on('elementor/init-components', async () => {
	  try { await elementor.modules.nestedElements; } catch (e) {}
	  registerOnce();
	});
  
	// 2) If nested is already ready by the time we run, register immediately
	registerOnce();
  
	// 3) Fallback to the older signal (namespaced + one-time)
	const $w = elementorCommon?.elements?.$window;
	if ($w) {
	  $w.off('elementor/nested-element-type-loaded.aae')
		.one('elementor/nested-element-type-loaded.aae', registerOnce);
	}
  
	// 4) Tiny retry loop for boot gaps (clears itself after success)
	const poll = setInterval(() => { if (registerOnce()) clearInterval(poll); }, 50);
  });
  