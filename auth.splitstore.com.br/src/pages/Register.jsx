
// Componente de Registro
function Register({ onNavigate }) {
  const [formData, setFormData] = useState({
    nome: '',
    email: '',
    senha: '',
    telefone: '',
    discord: '',
    cpf: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [message, setMessage] = useState(null);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

const handleSubmit = async (e) => {
  e.preventDefault();
  
  if (!formData.termos) {
    setErrors({ termos: 'Você precisa aceitar os termos de uso' });
    return;
  }

  setLoading(true);
  setErrors({});
  setMessage(null);

  try {
    const response = await fetch('/api/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    });

    const data = await response.json();
    
    if (response.ok && data.success) {
  setMessage({ type: 'success', text: data.message });
  
  console.log('✅ REGISTRO BEM-SUCEDIDO!');
  console.log('🔑 Token:', data.token);
  
  // Salvar no localStorage como backup
  localStorage.setItem('auth_token', data.token);
  localStorage.setItem('user', JSON.stringify(data.user));
  
  // USAR HASH (#) EM VEZ DE QUERY PARAM (?)
  const redirectUrl = `https://dashboard.splitstore.com.br#token=${data.token}`;
  console.log('🔄 URL de redirecionamento (COM HASH):', redirectUrl);
  
  setTimeout(() => {
    console.log('🚀 REDIRECIONANDO...');
    window.location.href = redirectUrl;
  }, 1500);
} else {
      if (data.errors) {
        setErrors(data.errors);
      } else if (data.error) {
        setMessage({ type: 'error', text: data.error });
      }
    }
  } catch (error) {
    setMessage({ type: 'error', text: 'Erro ao criar conta. Tente novamente.' });
  } finally {
    setLoading(false);
  }

    // Simulação de registro
    setTimeout(() => {
      if (formData.nome && formData.email && formData.senha) {
        setMessage({ type: 'success', text: 'Conta criada com sucesso!' });
      } else {
        setErrors({
          nome: !formData.nome ? 'Nome é obrigatório' : '',
          email: !formData.email ? 'Email é obrigatório' : '',
          senha: !formData.senha ? 'Senha é obrigatória' : ''
        });
      }
      setLoading(false);
    }, 1000);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Logo */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center gap-3 mb-6">
            <div className="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center shadow-lg">
              <span className="text-white text-2xl font-bold">S</span>
            </div>
            <span className="text-3xl font-bold text-gray-800">
              Split<span className="text-red-600">Store</span>
            </span>
          </div>
          <h2 className="text-2xl font-bold text-gray-800 mb-2">Criar conta</h2>
          <p className="text-gray-600 text-sm">
            Insira seus dados para começar.
          </p>
        </div>

        {/* Card */}
        <div className="bg-white rounded-2xl shadow-xl p-8 border border-gray-200">
          {/* Message */}
          {message && (
            <div className={`flex items-center gap-3 p-4 rounded-lg mb-6 ${
              message.type === 'success' 
                ? 'bg-green-50 text-green-800 border border-green-200' 
                : 'bg-red-50 text-red-800 border border-red-200'
            }`}>
              <svg className="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                {message.type === 'success' ? (
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"/>
                ) : (
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd"/>
                )}
              </svg>
              <p className="text-sm font-medium">{message.text}</p>
            </div>
          )}

          <div className="space-y-4">
            {/* Nome */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Nome completo
              </label>
              <input
                type="text"
                name="nome"
                value={formData.nome}
                onChange={handleChange}
                className={`w-full bg-gray-50 border ${
                  errors.nome ? 'border-red-500' : 'border-gray-300'
                } rounded-lg px-4 py-3 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all`}
                placeholder="Digite seu nome completo"
              />
              {errors.nome && (
                <p className="text-red-500 text-xs mt-2">{errors.nome}</p>
              )}
            </div>

            {/* Email */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                E-mail
              </label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                className={`w-full bg-gray-50 border ${
                  errors.email ? 'border-red-500' : 'border-gray-300'
                } rounded-lg px-4 py-3 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all`}
                placeholder="Digite seu e-mail"
              />
              {errors.email && (
                <p className="text-red-500 text-xs mt-2">{errors.email}</p>
              )}
            </div>

            {/* Senha */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                Senha
              </label>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  name="senha"
                  value={formData.senha}
                  onChange={handleChange}
                  className={`w-full bg-gray-50 border ${
                    errors.senha ? 'border-red-500' : 'border-gray-300'
                  } rounded-lg px-4 py-3 pr-12 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all`}
                  placeholder="Digite sua senha"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 transition-colors"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {showPassword ? (
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    ) : (
                      <>
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </>
                    )}
                  </svg>
                </button>
              </div>
              {errors.senha && (
                <p className="text-red-500 text-xs mt-2">{errors.senha}</p>
              )}
            </div>

            {/* Grid com 2 colunas */}
            <div className="grid grid-cols-2 gap-4">
              {/* Telefone */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Telefone
                </label>
                <input
                  type="tel"
                  name="telefone"
                  value={formData.telefone}
                  onChange={handleChange}
                  className="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                  placeholder="Digite seu telefone"
                />
              </div>

              {/* Discord */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Discord (Opcional)
                </label>
                <input
                  type="text"
                  name="discord"
                  value={formData.discord}
                  onChange={handleChange}
                  className="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                  placeholder="Digite seu ID Discord"
                />
              </div>
            </div>

            {/* CPF */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">
                CPF (Opcional)
              </label>
              <input
                type="text"
                name="cpf"
                value={formData.cpf}
                onChange={handleChange}
                className="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                placeholder="Digite seu CPF"
              />
            </div>

            {/* Submit */}
            <button
              onClick={handleSubmit}
              disabled={loading}
              className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-400 disabled:cursor-not-allowed text-white py-3.5 rounded-lg font-semibold transition-all shadow-lg shadow-red-500/30 hover:shadow-xl hover:shadow-red-500/40 flex items-center justify-center gap-2"
            >
              {loading ? (
                <>
                  <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  Criando conta...
                </>
              ) : (
                'Criar conta'
              )}
            </button>
          </div>

          {/* Divider */}
          <div className="relative my-6">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-gray-200"></div>
            </div>
            <div className="relative flex justify-center text-xs">
              <span className="bg-white px-3 text-gray-500 font-medium">OU</span>
            </div>
          </div>

          {/* Login */}
          <div className="text-center">
            <p className="text-gray-600 text-sm mb-4">
              Já tem uma conta?
            </p>
            <button
              onClick={() => onNavigate('login')}
              className="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 py-3 rounded-lg font-semibold transition-all border border-gray-300"
            >
              Fazer login
            </button>
          </div>
        </div>

        {/* Footer */}
        <div className="text-center mt-6">
          <p className="text-gray-500 text-xs">
            © 2026 SplitStore. Todos os direitos reservados.
          </p>
        </div>
      </div>
    </div>
  );
}

// App Principal
export default function App() {
  const [currentPage, setCurrentPage] = useState('login');

  return currentPage === 'login' ? (
    <Login onNavigate={setCurrentPage} />
  ) : (
    <Register onNavigate={setCurrentPage} />
  );
}