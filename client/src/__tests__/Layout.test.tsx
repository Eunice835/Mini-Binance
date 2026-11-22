import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/react'
import { BrowserRouter } from 'react-router-dom'
import Layout from '../components/Layout'
import { useAuthStore } from '../store/authStore'

vi.mock('../store/authStore', () => ({
  useAuthStore: vi.fn(),
}))

describe('Layout', () => {
  beforeEach(() => {
    vi.mocked(useAuthStore).mockImplementation((selector?: unknown) => {
      const state = {
        user: { id: 1, name: 'Test User', email: 'test@example.com', role: 'user' },
        logout: vi.fn(),
      }
      if (typeof selector === 'function') {
        return selector(state)
      }
      return state
    })
  })

  it('renders navigation items', () => {
    render(
      <BrowserRouter>
        <Layout>
          <div>Test Content</div>
        </Layout>
      </BrowserRouter>
    )

    expect(screen.getByText('CryptoExchange')).toBeInTheDocument()
    expect(screen.getByText('Dashboard')).toBeInTheDocument()
    expect(screen.getByText('Trade')).toBeInTheDocument()
    expect(screen.getByText('Wallet')).toBeInTheDocument()
    expect(screen.getByText('Portfolio')).toBeInTheDocument()
  })

  it('renders children content', () => {
    render(
      <BrowserRouter>
        <Layout>
          <div>Test Content</div>
        </Layout>
      </BrowserRouter>
    )

    expect(screen.getByText('Test Content')).toBeInTheDocument()
  })

  it('displays user email', () => {
    render(
      <BrowserRouter>
        <Layout>
          <div>Test</div>
        </Layout>
      </BrowserRouter>
    )

    expect(screen.getByText('test@example.com')).toBeInTheDocument()
  })

  it('shows admin link for admin users', () => {
    vi.mocked(useAuthStore).mockImplementation((selector?: unknown) => {
      const state = {
        user: { id: 1, name: 'Admin', email: 'admin@example.com', role: 'admin' },
        logout: vi.fn(),
      }
      if (typeof selector === 'function') {
        return selector(state)
      }
      return state
    })

    render(
      <BrowserRouter>
        <Layout>
          <div>Test</div>
        </Layout>
      </BrowserRouter>
    )

    expect(screen.getByText('Admin')).toBeInTheDocument()
  })
})
