# Frontend Integration Guide - Matrimonial Platform API

## Overview

This guide helps frontend developers integrate with the Matrimonial Platform API. Whether you're building a React app, Vue.js application, Angular project, or mobile app, this guide provides practical examples and best practices.

## 🚀 Quick Start

### Base Configuration

```javascript
// config/api.js
const API_CONFIG = {
  baseURL: 'https://your-domain.com/api/v1',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
};

export default API_CONFIG;
```

### API Client Setup

```javascript
// services/apiClient.js
import axios from 'axios';
import API_CONFIG from '../config/api';

const apiClient = axios.create(API_CONFIG);

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized - redirect to login
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error.response?.data || error);
  }
);

export default apiClient;
```

## 🔐 Authentication Flow

### 1. User Registration

```javascript
// services/authService.js
import apiClient from './apiClient';

export const authService = {
  // Register new user
  async register(userData) {
    try {
      const response = await apiClient.post('/auth/register', {
        firstname: userData.firstname,
        lastname: userData.lastname,
        email: userData.email,
        mobile: userData.mobile,
        password: userData.password,
        password_confirmation: userData.confirmPassword,
        country_code: userData.countryCode,
        country: userData.country,
        agree: true
      });
      
      if (response.success) {
        localStorage.setItem('auth_token', response.data.token);
        return response.data;
      }
    } catch (error) {
      throw error;
    }
  },

  // Login user
  async login(username, password) {
    try {
      const response = await apiClient.post('/auth/login', {
        username, // Can be email or mobile
        password
      });
      
      if (response.success) {
        localStorage.setItem('auth_token', response.data.token);
        localStorage.setItem('user_data', JSON.stringify(response.data.user));
        return response.data;
      }
    } catch (error) {
      throw error;
    }
  },

  // Logout
  async logout() {
    try {
      await apiClient.post('/auth/logout');
    } finally {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user_data');
    }
  },

  // Get current user
  async getCurrentUser() {
    const response = await apiClient.get('/auth/user');
    return response.data;
  },

  // Social login
  async socialLogin(provider, providerData) {
    const response = await apiClient.post('/auth/social-login', {
      provider,
      provider_id: providerData.id,
      email: providerData.email,
      name: providerData.name
    });
    
    if (response.success) {
      localStorage.setItem('auth_token', response.data.token);
      return response.data;
    }
  }
};
```

### 2. React Authentication Hook

```javascript
// hooks/useAuth.js
import { useState, useEffect, useContext, createContext } from 'react';
import { authService } from '../services/authService';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user_data');
    
    if (token && userData) {
      setUser(JSON.parse(userData));
      setIsAuthenticated(true);
    }
    setLoading(false);
  }, []);

  const login = async (username, password) => {
    try {
      const data = await authService.login(username, password);
      setUser(data.user);
      setIsAuthenticated(true);
      return data;
    } catch (error) {
      throw error;
    }
  };

  const register = async (userData) => {
    try {
      const data = await authService.register(userData);
      setUser(data.user);
      setIsAuthenticated(true);
      return data;
    } catch (error) {
      throw error;
    }
  };

  const logout = async () => {
    await authService.logout();
    setUser(null);
    setIsAuthenticated(false);
  };

  const value = {
    user,
    loading,
    isAuthenticated,
    login,
    register,
    logout
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
```

## 👤 Profile Management

### Profile Service

```javascript
// services/profileService.js
import apiClient from './apiClient';

export const profileService = {
  // Get user profile
  async getProfile() {
    const response = await apiClient.get('/profile');
    return response.data;
  },

  // Update basic profile
  async updateProfile(profileData) {
    const response = await apiClient.put('/profile', profileData);
    return response.data;
  },

  // Update avatar
  async updateAvatar(imageFile) {
    const formData = new FormData();
    formData.append('avatar', imageFile);
    
    const response = await apiClient.post('/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  },

  // Update basic information
  async updateBasicInfo(basicInfo) {
    const response = await apiClient.put('/profile/basic-info', basicInfo);
    return response.data;
  },

  // Career management
  async addCareer(careerData) {
    const response = await apiClient.post('/profile/career', careerData);
    return response.data;
  },

  async updateCareer(id, careerData) {
    const response = await apiClient.put(`/profile/career/${id}`, careerData);
    return response.data;
  },

  async deleteCareer(id) {
    const response = await apiClient.delete(`/profile/career/${id}`);
    return response.data;
  },

  // Get profile completion status
  async getCompletionStatus() {
    const response = await apiClient.get('/profile/completion-status');
    return response.data;
  },

  // Submit KYC
  async submitKyc(kycData, documents) {
    const formData = new FormData();
    formData.append('kyc_data', JSON.stringify(kycData));
    
    Object.keys(documents).forEach(key => {
      formData.append(`documents[${key}]`, documents[key]);
    });

    const response = await apiClient.post('/kyc/submit', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    return response.data;
  }
};
```

### React Profile Components

```javascript
// components/ProfileForm.jsx
import React, { useState, useEffect } from 'react';
import { profileService } from '../services/profileService';

const ProfileForm = () => {
  const [profile, setProfile] = useState({});
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      setLoading(true);
      const data = await profileService.getProfile();
      setProfile(data.user);
    } catch (error) {
      console.error('Failed to load profile:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (formData) => {
    try {
      setLoading(true);
      setErrors({});
      
      const updatedProfile = await profileService.updateProfile(formData);
      setProfile(updatedProfile.user);
      
      // Show success message
      alert('Profile updated successfully!');
    } catch (error) {
      if (error.errors) {
        setErrors(error.errors);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleAvatarUpload = async (file) => {
    try {
      const result = await profileService.updateAvatar(file);
      setProfile(prev => ({ ...prev, image: result.data.avatar_url }));
    } catch (error) {
      console.error('Failed to upload avatar:', error);
    }
  };

  return (
    <div className="profile-form">
      {/* Profile form JSX */}
      <form onSubmit={(e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        handleSubmit(Object.fromEntries(formData));
      }}>
        {/* Form fields */}
      </form>
    </div>
  );
};
```

## 🔍 Search & Matchmaking

### Match Service

```javascript
// services/matchService.js
import apiClient from './apiClient';

export const matchService = {
  // Search matches with filters
  async searchMatches(filters = {}, page = 1) {
    const params = { ...filters, page };
    const response = await apiClient.get('/matches', { params });
    return response.data;
  },

  // Get recommendations
  async getRecommendations(page = 1) {
    const response = await apiClient.get('/matches/recommendations', {
      params: { page }
    });
    return response.data;
  },

  // Get profile details
  async getProfile(userId) {
    const response = await apiClient.get(`/matches/${userId}`);
    return response.data;
  },

  // Record profile view
  async viewProfile(userId) {
    const response = await apiClient.post(`/matches/${userId}/view`);
    return response.data;
  },

  // Interest management
  async sendInterest(userId) {
    const response = await apiClient.post(`/interactions/interests/${userId}/send`);
    return response.data;
  },

  async acceptInterest(interestId) {
    const response = await apiClient.post(`/interactions/interests/${interestId}/accept`);
    return response.data;
  },

  async declineInterest(interestId) {
    const response = await apiClient.post(`/interactions/interests/${interestId}/decline`);
    return response.data;
  },

  // Shortlist management
  async addToShortlist(userId) {
    const response = await apiClient.post(`/interactions/shortlist/${userId}`);
    return response.data;
  },

  async removeFromShortlist(userId) {
    const response = await apiClient.delete(`/interactions/shortlist/${userId}`);
    return response.data;
  },

  // Get user interactions
  async getInterests() {
    const response = await apiClient.get('/interactions/interests');
    return response.data;
  },

  async getShortlist() {
    const response = await apiClient.get('/interactions/shortlist');
    return response.data;
  }
};
```

### Search Component (React)

```javascript
// components/SearchMatches.jsx
import React, { useState, useEffect } from 'react';
import { matchService } from '../services/matchService';

const SearchMatches = () => {
  const [matches, setMatches] = useState([]);
  const [filters, setFilters] = useState({
    age_min: '',
    age_max: '',
    location: '',
    religion: ''
  });
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({});

  useEffect(() => {
    searchMatches();
  }, [filters]);

  const searchMatches = async (page = 1) => {
    try {
      setLoading(true);
      const data = await matchService.searchMatches(filters, page);
      
      if (page === 1) {
        setMatches(data.data);
      } else {
        setMatches(prev => [...prev, ...data.data]);
      }
      
      setPagination({
        currentPage: data.current_page,
        lastPage: data.last_page,
        hasMore: data.current_page < data.last_page
      });
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  const loadMore = () => {
    if (pagination.hasMore && !loading) {
      searchMatches(pagination.currentPage + 1);
    }
  };

  const handleSendInterest = async (userId) => {
    try {
      await matchService.sendInterest(userId);
      // Update UI to show interest sent
      setMatches(prev => prev.map(match => 
        match.id === userId 
          ? { ...match, interestSent: true }
          : match
      ));
    } catch (error) {
      alert(error.message || 'Failed to send interest');
    }
  };

  return (
    <div className="search-matches">
      {/* Search filters */}
      <div className="filters">
        <input
          type="number"
          placeholder="Min Age"
          value={filters.age_min}
          onChange={(e) => handleFilterChange('age_min', e.target.value)}
        />
        <input
          type="number"
          placeholder="Max Age"
          value={filters.age_max}
          onChange={(e) => handleFilterChange('age_max', e.target.value)}
        />
        {/* More filters */}
      </div>

      {/* Results */}
      <div className="matches-grid">
        {matches.map(match => (
          <div key={match.id} className="match-card">
            <img src={match.image} alt={match.firstname} />
            <h3>{match.firstname} {match.lastname}</h3>
            <p>{match.age} years, {match.city}</p>
            
            <div className="actions">
              <button onClick={() => handleSendInterest(match.id)}>
                Send Interest
              </button>
              <button onClick={() => matchService.addToShortlist(match.id)}>
                Shortlist
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Load more */}
      {pagination.hasMore && (
        <button onClick={loadMore} disabled={loading}>
          {loading ? 'Loading...' : 'Load More'}
        </button>
      )}
    </div>
  );
};
```

## 💬 Messaging System

### Message Service

```javascript
// services/messageService.js
import apiClient from './apiClient';

export const messageService = {
  // Get conversations
  async getConversations(page = 1) {
    const response = await apiClient.get('/messages', {
      params: { page }
    });
    return response.data;
  },

  // Get messages in conversation
  async getMessages(conversationId, page = 1) {
    const response = await apiClient.get(`/messages/${conversationId}`, {
      params: { page }
    });
    return response.data;
  },

  // Send message
  async sendMessage(receiverId, message, conversationId = null) {
    const response = await apiClient.post('/messages', {
      receiver_id: receiverId,
      message,
      conversation_id: conversationId
    });
    return response.data;
  },

  // Mark conversation as read
  async markAsRead(conversationId) {
    const response = await apiClient.post(`/messages/${conversationId}/mark-read`);
    return response.data;
  },

  // Get unread count
  async getUnreadCount() {
    const response = await apiClient.get('/messages/unread/count');
    return response.data;
  },

  // Search users for messaging
  async searchUsers(query) {
    const response = await apiClient.get('/messages/search/users', {
      params: { query }
    });
    return response.data;
  }
};
```

## 📱 Mobile App Integration (React Native)

### API Client Setup for React Native

```javascript
// services/apiClient.native.js
import AsyncStorage from '@react-native-async-storage/async-storage';
import axios from 'axios';

const apiClient = axios.create({
  baseURL: 'https://your-domain.com/api/v1',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Request interceptor
apiClient.interceptors.request.use(
  async (config) => {
    const token = await AsyncStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor
apiClient.interceptors.response.use(
  (response) => response.data,
  async (error) => {
    if (error.response?.status === 401) {
      await AsyncStorage.removeItem('auth_token');
      // Navigate to login screen
    }
    return Promise.reject(error.response?.data || error);
  }
);

export default apiClient;
```

### React Native Auth Service

```javascript
// services/authService.native.js
import AsyncStorage from '@react-native-async-storage/async-storage';
import apiClient from './apiClient.native';

export const authService = {
  async login(username, password) {
    try {
      const response = await apiClient.post('/auth/login', {
        username,
        password
      });
      
      if (response.success) {
        await AsyncStorage.setItem('auth_token', response.data.token);
        await AsyncStorage.setItem('user_data', JSON.stringify(response.data.user));
        return response.data;
      }
    } catch (error) {
      throw error;
    }
  },

  async logout() {
    try {
      await apiClient.post('/auth/logout');
    } finally {
      await AsyncStorage.multiRemove(['auth_token', 'user_data']);
    }
  },

  async getStoredUser() {
    const token = await AsyncStorage.getItem('auth_token');
    const userData = await AsyncStorage.getItem('user_data');
    
    if (token && userData) {
      return {
        token,
        user: JSON.parse(userData)
      };
    }
    return null;
  }
};
```

## 🎨 Vue.js Integration

### Vue Composition API

```javascript
// composables/useAuth.js
import { ref, computed } from 'vue';
import { authService } from '../services/authService';

const user = ref(null);
const loading = ref(false);

export function useAuth() {
  const isAuthenticated = computed(() => !!user.value);

  const login = async (credentials) => {
    try {
      loading.value = true;
      const data = await authService.login(credentials.username, credentials.password);
      user.value = data.user;
      return data;
    } catch (error) {
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const logout = async () => {
    try {
      await authService.logout();
      user.value = null;
    } catch (error) {
      console.error('Logout error:', error);
    }
  };

  const getCurrentUser = async () => {
    try {
      loading.value = true;
      const data = await authService.getCurrentUser();
      user.value = data.user;
      return data;
    } catch (error) {
      throw error;
    } finally {
      loading.value = false;
    }
  };

  return {
    user: readonly(user),
    loading: readonly(loading),
    isAuthenticated,
    login,
    logout,
    getCurrentUser
  };
}
```

### Vue Component Example

```vue
<!-- components/MatchSearch.vue -->
<template>
  <div class="match-search">
    <!-- Search filters -->
    <div class="filters">
      <input 
        v-model="filters.age_min" 
        type="number" 
        placeholder="Min Age"
        @input="searchMatches"
      />
      <input 
        v-model="filters.age_max" 
        type="number" 
        placeholder="Max Age"
        @input="searchMatches"
      />
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="loading">
      Searching matches...
    </div>

    <!-- Results -->
    <div v-else class="matches-grid">
      <div 
        v-for="match in matches" 
        :key="match.id" 
        class="match-card"
      >
        <img :src="match.image" :alt="match.firstname" />
        <h3>{{ match.firstname }} {{ match.lastname }}</h3>
        <p>{{ match.age }} years, {{ match.city }}</p>
        
        <div class="actions">
          <button @click="sendInterest(match.id)">
            Send Interest
          </button>
          <button @click="addToShortlist(match.id)">
            Shortlist
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { matchService } from '../services/matchService';

const matches = ref([]);
const loading = ref(false);
const filters = reactive({
  age_min: '',
  age_max: '',
  location: '',
  religion: ''
});

const searchMatches = async () => {
  try {
    loading.value = true;
    const data = await matchService.searchMatches(filters);
    matches.value = data.data;
  } catch (error) {
    console.error('Search failed:', error);
  } finally {
    loading.value = false;
  }
};

const sendInterest = async (userId) => {
  try {
    await matchService.sendInterest(userId);
    // Update UI
  } catch (error) {
    alert(error.message);
  }
};

const addToShortlist = async (userId) => {
  try {
    await matchService.addToShortlist(userId);
    // Update UI
  } catch (error) {
    alert(error.message);
  }
};

onMounted(() => {
  searchMatches();
});

// Watch for filter changes
watch(filters, () => {
  searchMatches();
});
</script>
```

## ⚡ State Management

### Redux/Redux Toolkit (React)

```javascript
// store/authSlice.js
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { authService } from '../services/authService';

// Async thunks
export const loginUser = createAsyncThunk(
  'auth/login',
  async ({ username, password }, { rejectWithValue }) => {
    try {
      const data = await authService.login(username, password);
      return data;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  }
);

export const logoutUser = createAsyncThunk('auth/logout', async () => {
  await authService.logout();
});

const authSlice = createSlice({
  name: 'auth',
  initialState: {
    user: null,
    token: localStorage.getItem('auth_token'),
    loading: false,
    error: null,
    isAuthenticated: false
  },
  reducers: {
    clearError: (state) => {
      state.error = null;
    }
  },
  extraReducers: (builder) => {
    builder
      .addCase(loginUser.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(loginUser.fulfilled, (state, action) => {
        state.loading = false;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.isAuthenticated = true;
      })
      .addCase(loginUser.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(logoutUser.fulfilled, (state) => {
        state.user = null;
        state.token = null;
        state.isAuthenticated = false;
      });
  }
});

export const { clearError } = authSlice.actions;
export default authSlice.reducer;
```

### Vuex Store (Vue.js)

```javascript
// store/modules/auth.js
import { authService } from '@/services/authService';

const state = {
  user: null,
  token: localStorage.getItem('auth_token'),
  loading: false,
  error: null
};

const getters = {
  isAuthenticated: state => !!state.token,
  currentUser: state => state.user
};

const mutations = {
  SET_LOADING(state, loading) {
    state.loading = loading;
  },
  SET_USER(state, user) {
    state.user = user;
  },
  SET_TOKEN(state, token) {
    state.token = token;
  },
  SET_ERROR(state, error) {
    state.error = error;
  },
  CLEAR_AUTH(state) {
    state.user = null;
    state.token = null;
    state.error = null;
  }
};

const actions = {
  async login({ commit }, credentials) {
    try {
      commit('SET_LOADING', true);
      commit('SET_ERROR', null);
      
      const data = await authService.login(credentials.username, credentials.password);
      
      commit('SET_USER', data.user);
      commit('SET_TOKEN', data.token);
      
      return data;
    } catch (error) {
      commit('SET_ERROR', error.message);
      throw error;
    } finally {
      commit('SET_LOADING', false);
    }
  },

  async logout({ commit }) {
    try {
      await authService.logout();
    } finally {
      commit('CLEAR_AUTH');
    }
  }
};

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
};
```

## 🔄 Real-time Features

### WebSocket/Socket.io Integration

```javascript
// services/socketService.js
import io from 'socket.io-client';

class SocketService {
  constructor() {
    this.socket = null;
  }

  connect(token) {
    this.socket = io('wss://your-domain.com', {
      auth: {
        token
      }
    });

    this.socket.on('connect', () => {
      console.log('Connected to server');
    });

    this.socket.on('new_message', (message) => {
      // Handle new message
      this.handleNewMessage(message);
    });

    this.socket.on('interest_received', (interest) => {
      // Handle interest notification
      this.handleInterestReceived(interest);
    });
  }

  disconnect() {
    if (this.socket) {
      this.socket.disconnect();
      this.socket = null;
    }
  }

  handleNewMessage(message) {
    // Emit event for components to listen
    window.dispatchEvent(new CustomEvent('newMessage', {
      detail: message
    }));
  }

  handleInterestReceived(interest) {
    // Show notification
    window.dispatchEvent(new CustomEvent('interestReceived', {
      detail: interest
    }));
  }
}

export const socketService = new SocketService();
```

## 📋 Form Validation

### Validation Helper

```javascript
// utils/validation.js
export const validationRules = {
  required: (value) => !!value || 'This field is required',
  email: (value) => {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(value) || 'Invalid email format';
  },
  minLength: (min) => (value) => 
    value.length >= min || `Minimum ${min} characters required`,
  phone: (value) => {
    const pattern = /^\+?[\d\s-()]+$/;
    return pattern.test(value) || 'Invalid phone number';
  },
  age: (value) => {
    const age = parseInt(value);
    return (age >= 18 && age <= 100) || 'Age must be between 18 and 100';
  }
};

export const validateForm = (data, rules) => {
  const errors = {};
  
  Object.keys(rules).forEach(field => {
    const fieldRules = rules[field];
    const value = data[field];
    
    for (const rule of fieldRules) {
      const result = rule(value);
      if (result !== true) {
        errors[field] = result;
        break;
      }
    }
  });
  
  return {
    isValid: Object.keys(errors).length === 0,
    errors
  };
};
```

## 🚀 Best Practices

### 1. Error Handling

```javascript
// utils/errorHandler.js
export const handleApiError = (error) => {
  if (error.errors) {
    // Validation errors
    return {
      type: 'validation',
      message: 'Please check your input',
      errors: error.errors
    };
  }
  
  if (error.message) {
    return {
      type: 'api',
      message: error.message
    };
  }
  
  return {
    type: 'unknown',
    message: 'Something went wrong. Please try again.'
  };
};
```

### 2. Loading States

```javascript
// hooks/useApiCall.js
import { useState } from 'react';

export const useApiCall = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const execute = async (apiCall) => {
    try {
      setLoading(true);
      setError(null);
      const result = await apiCall();
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

### 3. Caching Strategy

```javascript
// utils/cache.js
class ApiCache {
  constructor() {
    this.cache = new Map();
    this.expiry = new Map();
  }

  set(key, data, ttl = 300000) { // 5 minutes default
    this.cache.set(key, data);
    this.expiry.set(key, Date.now() + ttl);
  }

  get(key) {
    const expiry = this.expiry.get(key);
    if (expiry && Date.now() > expiry) {
      this.cache.delete(key);
      this.expiry.delete(key);
      return null;
    }
    return this.cache.get(key);
  }

  clear() {
    this.cache.clear();
    this.expiry.clear();
  }
}

export const apiCache = new ApiCache();
```

## 🔧 Environment Configuration

### Environment Variables

```javascript
// config/environment.js
const config = {
  development: {
    apiUrl: 'http://localhost:8000/api/v1',
    socketUrl: 'ws://localhost:8000',
    enableLogs: true
  },
  production: {
    apiUrl: 'https://api.yourdomain.com/api/v1',
    socketUrl: 'wss://api.yourdomain.com',
    enableLogs: false
  }
};

const env = process.env.NODE_ENV || 'development';
export default config[env];
```

This frontend integration guide provides everything needed to build a modern matrimonial platform frontend that seamlessly integrates with the API. Choose the framework examples that match your tech stack and customize as needed.