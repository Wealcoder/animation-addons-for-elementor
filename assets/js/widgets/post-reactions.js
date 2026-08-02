(function() {
    /**
     * @param {Element} scope The Widget wrapper element
     */
     
    function AaeAddonGetVisitedPosts() {
        const visitedPosts = localStorage.getItem('aaeReactionsPosts');
        return visitedPosts ? JSON.parse(visitedPosts) : [];
    }
    
    function AaeAddonaddVisitedPost(postId) {
        const visitedPosts = AaeAddonGetVisitedPosts();
        if (!visitedPosts.includes(postId)) {
          visitedPosts.push(postId);
          localStorage.setItem('aaeReactionsPosts', JSON.stringify(visitedPosts));
        }
    }
    
    function AaeAddonDisbaleAllbtn(btns){
        const visitedPosts = AaeAddonGetVisitedPosts();
        btns.forEach(function(btn) {
            if(visitedPosts.includes(WCF_ADDONS_JS.post_id)){
                btn.disabled = true;
            }
        });
    }
    const AAEPost_Reactions = function(scope) {
       
        const visitedPosts = AaeAddonGetVisitedPosts();
        if(scope.length){    
            const reactionBtns = scope[0].querySelectorAll('.aaeaddon-reaction-btn');   
            reactionBtns.forEach(function(btn) {
                if(visitedPosts.includes(WCF_ADDONS_JS.post_id)){
                    btn.disabled = true;
                }
                btn.addEventListener('click', function() {
                    const reaction = btn.dataset.rtype;                    
                    // Show a loading state                  
                    btn.disabled = true;
                    // Make the AJAX request using Fetch API
                    fetch(WCF_ADDONS_JS.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'aaeaddon_post_reaction',
                            reaction: reaction,
                            post_id: WCF_ADDONS_JS.post_id, // Post ID to update share count
                            nonce : WCF_ADDONS_JS._wpnonce
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) { 
                        
                            if(btn.querySelector('.aae-reaction-count')){
                                btn.querySelector('.aae-reaction-count').textContent = data.data[reaction];       
                            }                                                  
                            btn.disabled = true;
                            AaeAddonaddVisitedPost(WCF_ADDONS_JS.post_id)
                            AaeAddonDisbaleAllbtn(reactionBtns);
                        } else {                           
                            btn.textContent = 'Try Again';
                            btn.disabled = false;
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.textContent = 'Retry';
                    });
                });
            });
        }
    };

    // Ensure the function runs under Elementor
    window.addEventListener('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/aaeaddon-post-reactions.default', AAEPost_Reactions);
    });
})();
