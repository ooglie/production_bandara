(function () {
    'use strict';

    var TEMPLATE_SELECTOR = '[data-bandara-kitchen-featured-chef-template]';
    var ROOT_SELECTOR = '[data-bandara-kitchen-original-chef-picks]';
    var APPLIED_ATTRIBUTE = 'data-bandara-kitchen-chef-applied';
    var RETRY_DELAYS = [0, 40, 120, 300, 700, 1400];

    function normalise(value) {
        return String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function elementText(element) {
        return normalise(element && element.textContent);
    }

    function hrefs(element) {
        if (!element || !element.querySelectorAll) {
            return [];
        }

        var values = [];
        if (element.matches && element.matches('a[href]')) {
            values.push(element.getAttribute('href') || '');
        }
        element.querySelectorAll('a[href]').forEach(function (anchor) {
            values.push(anchor.getAttribute('href') || '');
        });
        return values.map(normalise);
    }

    function attributeSignal(element) {
        if (!element || !element.getAttributeNames) {
            return '';
        }

        return normalise(element.getAttributeNames().map(function (name) {
            return name + ' ' + (element.getAttribute(name) || '');
        }).join(' '));
    }

    function strongRecipeScore(element) {
        if (!element) {
            return 0;
        }

        var score = 0;
        var text = elementText(element);
        var attrs = attributeSignal(element);
        var links = hrefs(element);

        links.forEach(function (href) {
            if (/(^|\/)recipes?(\/|$|\?)/.test(href) || /kitchen\/recipes?/.test(href)) {
                score += 10;
            } else if (/recipe/.test(href)) {
                score += 4;
            }
        });

        if (/\b(view|read|open|discover)\s+(the\s+)?recipe\b/.test(text)) {
            score += 8;
        }
        if (/\brecipe\s+(of|for)\b|\bfeatured\s+recipe\b|\brecipe\s+of\s+the\s+(day|week)\b/.test(text)) {
            score += 6;
        }
        if (/\brecipe\b/.test(text)) {
            score += 2;
        }
        if (/recipe/.test(attrs)) {
            score += 5;
        }

        return score;
    }

    function kitchenScore(element) {
        if (!element) {
            return 0;
        }

        var score = 0;
        var text = elementText(element);
        var attrs = attributeSignal(element);
        var links = hrefs(element);

        if (text.indexOf('bandara kitchen') !== -1) {
            score += 12;
        }
        if (text.indexOf('chef picks') !== -1 || text.indexOf("chef's picks") !== -1) {
            score += 10;
        }
        if (/\bchef\s+spotlight\b|\bfrom\s+the\s+kitchen\b/.test(text)) {
            score += 6;
        }
        if (/\bchef\b/.test(text)) {
            score += 2;
        }

        links.forEach(function (href) {
            if (/collections\/chef[-_]?picks/.test(href)) {
                score += 10;
            } else if (/(^|\/)kitchen(\/|$|\?)/.test(href) && !/kitchen\/recipes?/.test(href)) {
                score += 7;
            }
        });

        if (/chef[-_]?picks|bandara[-_ ]?kitchen|kitchen[-_ ]?card/.test(attrs)) {
            score += 7;
        }

        return score;
    }

    function directChildren(parent) {
        return Array.prototype.filter.call(parent && parent.children ? parent.children : [], function (child) {
            return !child.matches('script, style, template') && !child.hasAttribute(APPLIED_ATTRIBUTE);
        });
    }

    function depthWithin(element, root) {
        var depth = 0;
        var current = element;
        while (current && current !== root) {
            depth += 1;
            current = current.parentElement;
        }
        return depth;
    }

    function candidatePairs(root) {
        var parents = [root].concat(Array.prototype.slice.call(root.querySelectorAll('*')));
        var pairs = [];

        parents.forEach(function (parent) {
            var children = directChildren(parent);
            if (children.length < 2 || children.length > 6) {
                return;
            }

            var recipeIndex = -1;
            var recipeScore = 0;
            var kitchenIndex = -1;
            var kitchenCardScore = 0;

            children.forEach(function (child, index) {
                var currentRecipeScore = strongRecipeScore(child);
                var currentKitchenScore = kitchenScore(child);

                if (currentRecipeScore > recipeScore) {
                    recipeScore = currentRecipeScore;
                    recipeIndex = index;
                }
                if (currentKitchenScore > kitchenCardScore) {
                    kitchenCardScore = currentKitchenScore;
                    kitchenIndex = index;
                }
            });

            if (recipeIndex === -1 || kitchenIndex === -1 || recipeIndex === kitchenIndex) {
                return;
            }
            if (recipeScore < 7 || kitchenCardScore < 5) {
                return;
            }

            var left = children[kitchenIndex];
            var recipe = children[recipeIndex];
            var leftRecipeScore = strongRecipeScore(left);
            if (leftRecipeScore >= recipeScore) {
                return;
            }

            var score = recipeScore * 4 + kitchenCardScore * 3;
            if (children.length === 2) {
                score += 18;
            }
            if (kitchenIndex < recipeIndex) {
                score += 8;
            }
            score += Math.min(depthWithin(parent, root), 12);
            score -= leftRecipeScore * 2;

            pairs.push({
                left: left,
                recipe: recipe,
                parent: parent,
                score: score,
                recipeScore: recipeScore,
                kitchenScore: kitchenCardScore
            });
        });

        return pairs.sort(function (a, b) {
            return b.score - a.score;
        });
    }

    function fallbackPair(root) {
        var parents = [root].concat(Array.prototype.slice.call(root.querySelectorAll('*')));
        var matches = [];

        parents.forEach(function (parent) {
            var children = directChildren(parent);
            if (children.length !== 2) {
                return;
            }

            var firstRecipe = strongRecipeScore(children[0]);
            var secondRecipe = strongRecipeScore(children[1]);
            if (Math.max(firstRecipe, secondRecipe) < 10 || firstRecipe === secondRecipe) {
                return;
            }

            var recipeIndex = firstRecipe > secondRecipe ? 0 : 1;
            var leftIndex = recipeIndex === 0 ? 1 : 0;
            var left = children[leftIndex];
            var text = elementText(left);
            var links = hrefs(left).join(' ');

            if (!/chef|kitchen/.test(text + ' ' + links)) {
                return;
            }

            matches.push({
                left: left,
                recipe: children[recipeIndex],
                parent: parent,
                score: 20 + depthWithin(parent, root)
            });
        });

        matches.sort(function (a, b) {
            return b.score - a.score;
        });
        return matches[0] || null;
    }

    function findKitchenCard(root) {
        var pairs = candidatePairs(root);
        if (pairs.length > 0) {
            if (pairs.length === 1 || pairs[0].score - pairs[1].score >= 4 || pairs[0].left === pairs[1].left) {
                return pairs[0];
            }
        }

        return fallbackPair(root);
    }

    function removeConflictingUtilityClasses(element) {
        if (!element || !element.classList) {
            return;
        }

        Array.prototype.slice.call(element.classList).forEach(function (token) {
            if (/^(p[trblxy]?)-/.test(token) || /^(bg|text)-/.test(token) || /^dark:(bg|text)-/.test(token)) {
                element.classList.remove(token);
            }
        });
    }

    function installChef(template, pair) {
        var left = pair.left;
        var recipe = pair.recipe;
        var chefUrl = template.getAttribute('data-chef-url') || '';
        var chefName = template.getAttribute('data-chef-name') || 'featured Chef';

        if (!left || !recipe || !chefUrl || left === recipe || recipe.contains(left) || left.contains(recipe)) {
            return false;
        }
        if (left.hasAttribute(APPLIED_ATTRIBUTE)) {
            template.remove();
            return true;
        }

        var recipeFingerprint = recipe.innerHTML;
        var leftSnapshot = {
            html: left.innerHTML,
            className: left.getAttribute('class'),
            style: left.getAttribute('style'),
            href: left.getAttribute('href'),
            ariaLabel: left.getAttribute('aria-label')
        };

        function restoreLeft() {
            left.innerHTML = leftSnapshot.html;
            [['class', leftSnapshot.className], ['style', leftSnapshot.style], ['href', leftSnapshot.href], ['aria-label', leftSnapshot.ariaLabel]].forEach(function (entry) {
                if (entry[1] === null) {
                    left.removeAttribute(entry[0]);
                } else {
                    left.setAttribute(entry[0], entry[1]);
                }
            });
            left.removeAttribute(APPLIED_ATTRIBUTE);
            left.removeAttribute('data-bandara-kitchen-chef-version');
        }

        try {
            left.innerHTML = template.innerHTML;
            left.setAttribute(APPLIED_ATTRIBUTE, 'true');
            left.setAttribute('data-bandara-kitchen-chef-version', '1.2.3');
            left.classList.add('group');
            removeConflictingUtilityClasses(left);
            left.style.padding = '0';
            left.style.backgroundImage = 'none';
            left.style.position = left.style.position || 'relative';
            left.style.overflow = 'hidden';

            if (left.matches('a[href]')) {
                left.setAttribute('href', chefUrl);
                left.setAttribute('aria-label', 'Meet ' + chefName);
            } else {
                var overlay = document.createElement('a');
                overlay.href = chefUrl;
                overlay.setAttribute('aria-label', 'Meet ' + chefName);
                overlay.setAttribute('data-bandara-kitchen-chef-overlay-link', 'true');
                overlay.style.position = 'absolute';
                overlay.style.inset = '0';
                overlay.style.zIndex = '20';
                overlay.style.borderRadius = 'inherit';
                left.appendChild(overlay);
            }

            if (recipe.innerHTML !== recipeFingerprint) {
                restoreLeft();
                return false;
            }
        } catch (error) {
            restoreLeft();
            return false;
        }

        template.remove();
        return true;
    }

    function processTemplate(template) {
        if (!template || !template.isConnected) {
            return true;
        }

        var root = template.previousElementSibling;
        if (!root || !root.matches(ROOT_SELECTOR)) {
            root = template.parentElement ? template.parentElement.querySelector(ROOT_SELECTOR) : null;
        }
        if (!root) {
            root = document.querySelector(ROOT_SELECTOR);
        }
        if (!root) {
            return false;
        }

        var pair = findKitchenCard(root);
        if (!pair) {
            return false;
        }

        return installChef(template, pair);
    }

    function run() {
        var templates = Array.prototype.slice.call(document.querySelectorAll(TEMPLATE_SELECTOR));
        templates.forEach(processTemplate);
        return templates.length === 0 || templates.every(function (template) {
            return !template.isConnected;
        });
    }

    RETRY_DELAYS.forEach(function (delay) {
        window.setTimeout(run, delay);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }

    var observer = new MutationObserver(function () {
        if (run()) {
            observer.disconnect();
        }
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });
    window.setTimeout(function () {
        observer.disconnect();
    }, 2200);
}());
