export interface User {
  id: string
  email: string
  firstname: string
  lastname: string
  pending?: boolean
}

export interface UserState {
  user: User | null
  loading: boolean
  error: string | null
}
