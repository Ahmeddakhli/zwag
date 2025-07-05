# Frontend Quick Reference Guide

## 🚀 Essential API Calls

### Authentication

```javascript
// Login
const loginUser = async (username, password) => {
  const response = await apiClient.post('/auth/login', { username, password });
  localStorage.setItem('auth_token', response.data.token);
  return response.data;
};

// Register
const registerUser = async (userData) => {
  const response = await apiClient.post('/auth/register', userData);
  localStorage.setItem('auth_token', response.data.token);
  return response.data;
};

// Logout
const logoutUser = async () => {
  await apiClient.post('/auth/logout');
  localStorage.removeItem('auth_token');
};

// Get current user
const getCurrentUser = async () => {
  const response = await apiClient.get('/auth/user');
  return response.data;
};
```

### Profile Management

```javascript
// Get profile
const getProfile = async () => {
  const response = await apiClient.get('/profile');
  return response.data;
};

// Update profile
const updateProfile = async (data) => {
  const response = await apiClient.put('/profile', data);
  return response.data;
};

// Upload avatar
const uploadAvatar = async (file) => {
  const formData = new FormData();
  formData.append('avatar', file);
  const response = await apiClient.post('/profile/avatar', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  });
  return response.data;
};

// Add career info
const addCareer = async (careerData) => {
  const response = await apiClient.post('/profile/career', careerData);
  return response.data;
};
```

### Search & Matching

```javascript
// Search matches
const searchMatches = async (filters, page = 1) => {
  const response = await apiClient.get('/matches', {
    params: { ...filters, page }
  });
  return response.data;
};

// Get recommendations
const getRecommendations = async () => {
  const response = await apiClient.get('/matches/recommendations');
  return response.data;
};

// Send interest
const sendInterest = async (userId) => {
  const response = await apiClient.post(`/interactions/interests/${userId}/send`);
  return response.data;
};

// Add to shortlist
const addToShortlist = async (userId) => {
  const response = await apiClient.post(`/interactions/shortlist/${userId}`);
  return response.data;
};
```

### Messaging

```javascript
// Get conversations
const getConversations = async () => {
  const response = await apiClient.get('/messages');
  return response.data;
};

// Send message
const sendMessage = async (receiverId, message, conversationId = null) => {
  const response = await apiClient.post('/messages', {
    receiver_id: receiverId,
    message,
    conversation_id: conversationId
  });
  return response.data;
};

// Get unread count
const getUnreadCount = async () => {
  const response = await apiClient.get('/messages/unread/count');
  return response.data.unread_count;
};
```

## ⚠️ Error Handling Patterns

### Standard Error Handler

```javascript
const handleApiError = (error) => {
  if (error.response) {
    // Server responded with error status
    const { status, data } = error.response;
    
    switch (status) {
      case 401:
        // Unauthorized - redirect to login
        localStorage.removeItem('auth_token');
        window.location.href = '/login';
        break;
      case 422:
        // Validation errors
        return {
          type: 'validation',
          errors: data.errors || {},
          message: data.message || 'Validation failed'
        };
      case 403:
        return {
          type: 'forbidden',
          message: 'Access denied'
        };
      case 404:
        return {
          type: 'not_found',
          message: 'Resource not found'
        };
      case 500:
        return {
          type: 'server_error',
          message: 'Server error. Please try again later.'
        };
      default:
        return {
          type: 'api_error',
          message: data.message || 'Something went wrong'
        };
    }
  } else if (error.request) {
    // Network error
    return {
      type: 'network_error',
      message: 'Network error. Please check your connection.'
    };
  } else {
    // Other error
    return {
      type: 'unknown_error',
      message: 'An unexpected error occurred'
    };
  }
};
```

### React Error Boundary

```jsx
// components/ErrorBoundary.jsx
import React from 'react';

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Error caught by boundary:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="error-boundary">
          <h2>Something went wrong</h2>
          <p>We're sorry, but something unexpected happened.</p>
          <button onClick={() => window.location.reload()}>
            Reload Page
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
```

## 🔄 Loading States

### Loading Hook

```javascript
// hooks/useLoading.js
import { useState } from 'react';

export const useLoading = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const execute = async (asyncFunction) => {
    try {
      setLoading(true);
      setError(null);
      const result = await asyncFunction();
      return result;
    } catch (err) {
      setError(err);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return { loading, error, execute };
};
```

### Loading Component

```jsx
// components/LoadingSpinner.jsx
import React from 'react';

const LoadingSpinner = ({ size = 'medium', message = 'Loading...' }) => {
  const sizeClass = {
    small: 'spinner-small',
    medium: 'spinner-medium',
    large: 'spinner-large'
  }[size];

  return (
    <div className="loading-container">
      <div className={`spinner ${sizeClass}`}></div>
      {message && <p className="loading-message">{message}</p>}
    </div>
  );
};

export default LoadingSpinner;
```

## 📝 Form Validation

### Validation Rules

```javascript
// utils/validationRules.js
export const validationRules = {
  required: (value) => !!value || 'This field is required',
  
  email: (value) => {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(value) || 'Invalid email format';
  },
  
  password: (value) => {
    if (value.length < 6) return 'Password must be at least 6 characters';
    if (!/(?=.*[a-z])/.test(value)) return 'Password must contain lowercase letter';
    if (!/(?=.*[A-Z])/.test(value)) return 'Password must contain uppercase letter';
    if (!/(?=.*\d)/.test(value)) return 'Password must contain a number';
    return true;
  },
  
  phone: (value) => {
    const pattern = /^\+?[\d\s-()]+$/;
    return pattern.test(value) || 'Invalid phone number';
  },
  
  age: (value) => {
    const age = parseInt(value);
    return (age >= 18 && age <= 100) || 'Age must be between 18 and 100';
  },
  
  minLength: (min) => (value) => 
    value.length >= min || `Minimum ${min} characters required`,
    
  maxLength: (max) => (value) => 
    value.length <= max || `Maximum ${max} characters allowed`
};
```

### Form Validation Hook

```javascript
// hooks/useFormValidation.js
import { useState } from 'react';

export const useFormValidation = (initialState, validationRules) => {
  const [values, setValues] = useState(initialState);
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});

  const validate = (name, value) => {
    const rules = validationRules[name];
    if (!rules) return '';

    for (const rule of rules) {
      const result = rule(value);
      if (result !== true) {
        return result;
      }
    }
    return '';
  };

  const handleChange = (name, value) => {
    setValues(prev => ({ ...prev, [name]: value }));
    
    if (touched[name]) {
      const error = validate(name, value);
      setErrors(prev => ({ ...prev, [name]: error }));
    }
  };

  const handleBlur = (name) => {
    setTouched(prev => ({ ...prev, [name]: true }));
    const error = validate(name, values[name]);
    setErrors(prev => ({ ...prev, [name]: error }));
  };

  const validateAll = () => {
    const newErrors = {};
    let isValid = true;

    Object.keys(validationRules).forEach(name => {
      const error = validate(name, values[name]);
      if (error) {
        newErrors[name] = error;
        isValid = false;
      }
    });

    setErrors(newErrors);
    setTouched(Object.keys(validationRules).reduce((acc, key) => {
      acc[key] = true;
      return acc;
    }, {}));

    return isValid;
  };

  return {
    values,
    errors,
    touched,
    handleChange,
    handleBlur,
    validateAll,
    setValues,
    setErrors
  };
};
```

## 🎨 UI Components

### Button Component

```jsx
// components/Button.jsx
import React from 'react';

const Button = ({ 
  children, 
  variant = 'primary', 
  size = 'medium', 
  loading = false, 
  disabled = false,
  onClick,
  ...props 
}) => {
  const baseClass = 'btn';
  const variantClass = `btn-${variant}`;
  const sizeClass = `btn-${size}`;
  const disabledClass = (disabled || loading) ? 'btn-disabled' : '';

  return (
    <button
      className={`${baseClass} ${variantClass} ${sizeClass} ${disabledClass}`}
      onClick={onClick}
      disabled={disabled || loading}
      {...props}
    >
      {loading ? (
        <span className="btn-loading">
          <span className="spinner-small"></span>
          Loading...
        </span>
      ) : (
        children
      )}
    </button>
  );
};

export default Button;
```

### Input Component

```jsx
// components/Input.jsx
import React from 'react';

const Input = ({ 
  label, 
  error, 
  touched, 
  className = '',
  ...props 
}) => {
  const hasError = touched && error;
  
  return (
    <div className={`input-group ${className}`}>
      {label && <label className="input-label">{label}</label>}
      <input 
        className={`input ${hasError ? 'input-error' : ''}`}
        {...props}
      />
      {hasError && <span className="error-text">{error}</span>}
    </div>
  );
};

export default Input;
```

### Modal Component

```jsx
// components/Modal.jsx
import React, { useEffect } from 'react';

const Modal = ({ isOpen, onClose, title, children, size = 'medium' }) => {
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'unset';
    }

    return () => {
      document.body.style.overflow = 'unset';
    };
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div 
        className={`modal modal-${size}`}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="modal-header">
          <h3 className="modal-title">{title}</h3>
          <button className="modal-close" onClick={onClose}>
            ×
          </button>
        </div>
        <div className="modal-content">
          {children}
        </div>
      </div>
    </div>
  );
};

export default Modal;
```

## 📱 Responsive Patterns

### Responsive Hook

```javascript
// hooks/useResponsive.js
import { useState, useEffect } from 'react';

export const useResponsive = () => {
  const [windowSize, setWindowSize] = useState({
    width: window.innerWidth,
    height: window.innerHeight,
  });

  useEffect(() => {
    const handleResize = () => {
      setWindowSize({
        width: window.innerWidth,
        height: window.innerHeight,
      });
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  return {
    ...windowSize,
    isMobile: windowSize.width < 768,
    isTablet: windowSize.width >= 768 && windowSize.width < 1024,
    isDesktop: windowSize.width >= 1024,
  };
};
```

### Media Query Hook

```javascript
// hooks/useMediaQuery.js
import { useState, useEffect } from 'react';

export const useMediaQuery = (query) => {
  const [matches, setMatches] = useState(false);

  useEffect(() => {
    const media = window.matchMedia(query);
    if (media.matches !== matches) {
      setMatches(media.matches);
    }
    
    const listener = () => setMatches(media.matches);
    media.addListener(listener);
    
    return () => media.removeListener(listener);
  }, [matches, query]);

  return matches;
};
```

## 🔄 Data Fetching Patterns

### SWR Pattern (React)

```javascript
// hooks/useSWR.js
import { useState, useEffect, useRef } from 'react';

export const useSWR = (key, fetcher, options = {}) => {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);
  const cache = useRef(new Map());

  const { revalidateOnFocus = true, refreshInterval } = options;

  const fetchData = async (forceRefresh = false) => {
    if (!forceRefresh && cache.current.has(key)) {
      setData(cache.current.get(key));
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      const result = await fetcher();
      setData(result);
      setError(null);
      cache.current.set(key, result);
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [key]);

  useEffect(() => {
    if (refreshInterval) {
      const interval = setInterval(() => fetchData(true), refreshInterval);
      return () => clearInterval(interval);
    }
  }, [refreshInterval]);

  useEffect(() => {
    if (revalidateOnFocus) {
      const handleFocus = () => fetchData(true);
      window.addEventListener('focus', handleFocus);
      return () => window.removeEventListener('focus', handleFocus);
    }
  }, [revalidateOnFocus]);

  const mutate = (newData) => {
    setData(newData);
    cache.current.set(key, newData);
  };

  return {
    data,
    error,
    loading,
    mutate,
    refetch: () => fetchData(true)
  };
};
```

## 🎯 Common Use Cases

### Infinite Scroll

```javascript
// hooks/useInfiniteScroll.js
import { useState, useEffect, useCallback } from 'react';

export const useInfiniteScroll = (fetchMore, hasMore) => {
  const [loading, setLoading] = useState(false);

  const handleScroll = useCallback(() => {
    if (loading || !hasMore) return;

    if (window.innerHeight + document.documentElement.scrollTop 
        >= document.documentElement.offsetHeight - 1000) {
      setLoading(true);
      fetchMore().finally(() => setLoading(false));
    }
  }, [loading, hasMore, fetchMore]);

  useEffect(() => {
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, [handleScroll]);

  return { loading };
};
```

### Debounced Search

```javascript
// hooks/useDebounce.js
import { useState, useEffect } from 'react';

export const useDebounce = (value, delay) => {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
};

// Usage in search component
const SearchComponent = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const debouncedSearchTerm = useDebounce(searchTerm, 500);

  useEffect(() => {
    if (debouncedSearchTerm) {
      // Perform search
      searchMatches(debouncedSearchTerm);
    }
  }, [debouncedSearchTerm]);

  return (
    <input
      type="text"
      value={searchTerm}
      onChange={(e) => setSearchTerm(e.target.value)}
      placeholder="Search matches..."
    />
  );
};
```

This quick reference provides essential patterns and code snippets that you can copy and adapt for your specific implementation needs.