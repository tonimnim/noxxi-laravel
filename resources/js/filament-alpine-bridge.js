/**
 * Filament Alpine Component Bridge
 * 
 * This script provides placeholder implementations for lazy-loaded Alpine components
 * to prevent "undefined" errors during initial page load. The real implementations
 * will override these when loaded via async-alpine.
 */

(function() {
    'use strict';
    
    // Map of all Filament form components that use lazy loading
    const filamentComponents = {
        'richEditorFormComponent': function({ state }) {
            return {
                state: state || '',
                init() {
                    // Placeholder - real implementation will override
                }
            };
        },
        'dateTimePickerFormComponent': function(config) {
            return {
                ...config,
                init() {
                    // Placeholder - real implementation will override
                }
            };
        },
        'selectFormComponent': function(config) {
            return {
                ...config,
                init() {
                    // Placeholder - real implementation will override
                }
            };
        },
        'fileUploadFormComponent': function(config) {
            return {
                state: config.state || [],
                ...config,
                init() {
                    // Placeholder - real implementation will override
                }
            };
        },
        'keyValueFormComponent': function(config) {
            return {
                ...config,
                init() {
                    // Placeholder - real implementation will override
                }
            };
        },
        'markdownEditorFormComponent': function(config) {
            return {
                state: config.state || '',
                ...config,
                init() {
                    // Placeholder - real implementation will override
                }
            };
        },
        'textareaFormComponent': function(config) {
            return {
                ...config,
                init() {
                    // Placeholder - real implementation will override
                }
            };
        },
        'colorPickerFormComponent': function(config) {
            return {
                state: config.state || '',
                ...config,
                init() {
                    // Placeholder - real implementation will override
                }
            };
        }
    };
    
    // Register all placeholder components
    Object.keys(filamentComponents).forEach(componentName => {
        if (typeof window[componentName] === 'undefined') {
            window[componentName] = filamentComponents[componentName];
        }
    });
    
    // Create placeholder for tagsInputFormComponent if it doesn't exist
    if (typeof window.tagsInputFormComponent === 'undefined') {
        window.tagsInputFormComponent = function({ state, splitKeys = [] }) {
            // Return a minimal working Alpine component that will be replaced
            // when the real component loads
            return {
                state: state || [],
                newTag: '',
                splitKeys: splitKeys,
                
                // Placeholder methods that match the real component's interface
                createTag: function() {
                    // Will be overridden by real implementation
                    if (this.newTag && this.newTag.trim() !== '') {
                        if (!this.state.includes(this.newTag.trim())) {
                            this.state.push(this.newTag.trim());
                        }
                        this.newTag = '';
                    }
                },
                
                deleteTag: function(tag) {
                    // Will be overridden by real implementation
                    this.state = this.state.filter(t => t !== tag);
                },
                
                reorderTags: function(event) {
                    // Will be overridden by real implementation
                    if (event.oldIndex !== undefined && event.newIndex !== undefined) {
                        const item = this.state.splice(event.oldIndex, 1)[0];
                        this.state.splice(event.newIndex, 0, item);
                        this.state = [...this.state];
                    }
                },
                
                // Input bindings object
                input: {
                    'x-on:blur': 'createTag()',
                    'x-model': 'newTag',
                    'x-on:keydown'(event) {
                        if (event.key === 'Enter' || (this.splitKeys && this.splitKeys.includes(event.key))) {
                            event.preventDefault();
                            event.stopPropagation();
                            this.$parent.createTag();
                        }
                    },
                    'x-on:paste'() {
                        this.$nextTick(() => {
                            if (!this.splitKeys || this.splitKeys.length === 0) {
                                this.$parent.createTag();
                                return;
                            }
                            const pattern = this.splitKeys
                                .map(key => key.replace(/[/\-\\^$*+?.()|[\]{}]/g, '\\$&'))
                                .join('|');
                            const parts = this.$parent.newTag.split(new RegExp(pattern, 'g'));
                            parts.forEach(part => {
                                this.$parent.newTag = part;
                                this.$parent.createTag();
                            });
                        });
                    }
                },
                
                // Initialize the component
                init() {
                    // Ensure state is an array
                    if (!Array.isArray(this.state)) {
                        this.state = [];
                    }
                    
                    // Watch for the real component to load and update methods
                    const checkInterval = setInterval(() => {
                        // Check if async-alpine has loaded the real component
                        if (window.Alpine && window.Alpine.data && window.Alpine.data('__tags_input_loaded__')) {
                            clearInterval(checkInterval);
                            
                            // If there's a way to detect the real component loaded,
                            // we could reinitialize here, but Alpine handles this
                        }
                    }, 100);
                    
                    // Clear the interval after 5 seconds to prevent memory leaks
                    setTimeout(() => clearInterval(checkInterval), 5000);
                }
            };
        };
    }
    
    // Register with Alpine when it initializes
    document.addEventListener('alpine:init', () => {
        // Override the async-alpine loader to properly register our components
        const originalLoader = window.AsyncAlpine;
        if (originalLoader && originalLoader._getModule) {
            const original_getModule = originalLoader._getModule.bind(originalLoader);
            originalLoader._getModule = async function(name) {
                const module = await original_getModule(name);
                
                // Register components globally based on their names
                if (name && module) {
                    const componentFunction = typeof module === 'function' ? module : 
                                            (module.default || module[name] || Object.values(module)[0]);
                    
                    if (componentFunction) {
                        // Map component names to their global function names
                        const componentMappings = {
                            'tags-input': 'tagsInputFormComponent',
                            'rich-editor': 'richEditorFormComponent',
                            'date-time-picker': 'dateTimePickerFormComponent',
                            'select': 'selectFormComponent',
                            'file-upload': 'fileUploadFormComponent',
                            'key-value': 'keyValueFormComponent',
                            'markdown-editor': 'markdownEditorFormComponent',
                            'textarea': 'textareaFormComponent',
                            'color-picker': 'colorPickerFormComponent'
                        };
                        
                        // Check each mapping and register if matched
                        Object.keys(componentMappings).forEach(key => {
                            if (name.includes(key)) {
                                window[componentMappings[key]] = componentFunction;
                                if (window.Alpine && window.Alpine.data) {
                                    window.Alpine.data(`__${key.replace('-', '_')}_loaded__`, () => ({}));
                                }
                            }
                        });
                    }
                }
                
                return module;
            };
        }
    });
    
    // Also listen for async-alpine initialization
    document.addEventListener('async-alpine:init', () => {
        // Ensure our placeholder is still available
        if (typeof window.tagsInputFormComponent === 'undefined') {
            console.warn('TagsInput component bridge: Re-registering placeholder');
            // Re-register the placeholder if needed
        }
    });
})();