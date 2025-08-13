STACK

we are using laravel
we are using vue for UI facing client
filament for dashboards
we're using PostgreSQL, currently running on docker

this laravel project will be used also as API by our Noxxi mobile app
the mobile app is on flutter ... main users are - users, organisers
we need to leave those API endpoints open for mobile app consumption

the system is not kenya based. 
its african based


# Development Rules and Action Plan

## Core Development Process

### **THINK → READ → THINK → EXECUTE**

Before implementing any feature or making changes:

1. **THINK FIRST** 
   - Understand the requirement completely
   - Consider the implications and dependencies
   - Plan the approach mentally

2. **READ RELATED FILES**
   - Increase awareness of existing code structure
   - Understand current implementations
   - Check for existing patterns and conventions
   - Avoid duplication and maintain consistency

3. **THINK AGAIN**
   - Re-evaluate the approach with the new context
   - Ensure the solution aligns with existing architecture
   - Consider edge cases and potential issues

4. **EXECUTE**
   - Implement the solution following the refined plan
   - Follow existing code patterns
   - Keep it simple and maintainable

## Key Principles

- **No over-engineering** - Keep solutions simple and straightforward
- **Consistency first** - Follow existing patterns in the codebase
- **Context awareness** - Always understand the surrounding code before making changes
- **Minimal navigation** - Keep UI clean and focused
- **User-centric naming** - Use terminology that fits all use cases (e.g., "Listings" instead of "Events")

