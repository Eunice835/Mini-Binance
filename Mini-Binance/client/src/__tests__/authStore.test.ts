import { describe, it, expect, beforeEach } from 'vitest'
import { useAuthStore } from '../store/authStore'

describe('authStore', () => {
  beforeEach(() => {
    useAuthStore.setState({
      user: null,
      token: null,
      isLoading: false,
    })
    localStorage.clear()
  })

  it('has initial state', () => {
    const state = useAuthStore.getState()
    
    expect(state.user).toBeNull()
    expect(state.token).toBeNull()
    expect(state.isLoading).toBe(false)
  })

  it('can set user', () => {
    const user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      role: 'user' as const,
      kyc_status: 'none' as const,
      mfa_enabled: false,
      is_frozen: false,
    }

    useAuthStore.setState({ user })
    
    expect(useAuthStore.getState().user).toEqual(user)
  })

  it('can set token', () => {
    useAuthStore.setState({ token: 'test-token-123' })
    
    expect(useAuthStore.getState().token).toBe('test-token-123')
  })

  it('can clear state on logout', () => {
    const user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      role: 'user' as const,
      kyc_status: 'none' as const,
      mfa_enabled: false,
      is_frozen: false,
    }

    useAuthStore.setState({ user, token: 'token123' })
    useAuthStore.setState({ user: null, token: null })

    expect(useAuthStore.getState().user).toBeNull()
    expect(useAuthStore.getState().token).toBeNull()
  })
})
