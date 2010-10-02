require 'rake/clean'

sources = %w(helpers route request response hash)
sources.map! { |source| "src/pipes/#{source}.php" }
sources.each { |source| file source }
file 'pipes.php' => sources do |task|
    puts 'creating pipes.php:'
    open(task.name, 'w') do |f|
        header = "<?php\n\nnamespace pipes;\n\n"
        header_regex = Regexp.new('^' + Regexp.escape(header))
        f.write header
        task.prerequisites.each do |source|
            puts "- merging #{source}\n"
            f.write open(source).read().gsub(header_regex, '').strip() + "\n\n"
        end
    end
end
CLEAN << 'pipes.php'
CLOBBER << 'pipes.php'

file 'docs/api.md'
file 'docs/api.html' => 'docs/api.md' do
    cd 'docs' do
        sh 'ronn', '-5', '-s', 'toc', 'api.md'
    end
end
CLEAN << 'docs/api.html'
CLOBBER << 'docs/api.html'

task :default => [:build]

task :build => [:clean, 'pipes.php', 'docs/api.html']

task :test do
    chdir 'tests' do
        sh 'php run.php'
    end
end
