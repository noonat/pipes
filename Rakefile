require 'rake/clean'
CLEAN.include('lib/')
CLOBBER.include('lib/')

sources = %w(helpers route request response hash)
sources.map! { |source| "src/pipes/#{source}.php" }
sources.each { |source| file source }
destination = 'lib/pipes.php'
file destination => sources do |task|
    mkdir 'lib'
    puts 'creating lib/pipes.php'
    open(task.name, 'w') do |f|
        header = "<?php\n\nnamespace pipes;\n\n"
        header_regex = Regexp.new('^' + Regexp.escape(header))
        f.write header
        task.prerequisites.each do |source|
            puts "... merging #{source}\n"
            f.write open(source).read().gsub(header_regex, '').strip() + "\n\n"
        end
    end
end

task :default => [:clean, :build]

task :build => destination

task :test do
    chdir 'tests' do
        sh 'php run.php'
    end
end
